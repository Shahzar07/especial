"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useReducer,
  useState,
} from "react";
import type { Product } from "@/lib/types";

export type CartLine = {
  key: string;
  slug: string;
  variantId: string;
  title: string;
  variantLabel: string;
  priceCents: number;
  image: string;
  quantity: number;
};

type Action =
  | { type: "add"; line: Omit<CartLine, "key" | "quantity">; quantity: number }
  | { type: "setQuantity"; key: string; quantity: number }
  | { type: "remove"; key: string }
  | { type: "hydrate"; lines: CartLine[] };

const STORAGE_KEY = "eg_cart_v1";
const lineKey = (slug: string, variantId: string) => `${slug}:${variantId}`;

function reducer(state: CartLine[], action: Action): CartLine[] {
  switch (action.type) {
    case "hydrate":
      return action.lines;
    case "add": {
      const key = lineKey(action.line.slug, action.line.variantId);
      const existing = state.find((l) => l.key === key);
      if (existing) {
        return state.map((l) =>
          l.key === key ? { ...l, quantity: l.quantity + action.quantity } : l,
        );
      }
      return [...state, { ...action.line, key, quantity: action.quantity }];
    }
    case "setQuantity":
      return action.quantity <= 0
        ? state.filter((l) => l.key !== action.key)
        : state.map((l) =>
            l.key === action.key ? { ...l, quantity: action.quantity } : l,
          );
    case "remove":
      return state.filter((l) => l.key !== action.key);
  }
}

type CartContext = {
  lines: CartLine[];
  count: number;
  subtotalCents: number;
  isOpen: boolean;
  open: () => void;
  close: () => void;
  add: (product: Product, variantId: string, quantity?: number) => void;
  setQuantity: (key: string, quantity: number) => void;
  remove: (key: string) => void;
};

const Ctx = createContext<CartContext | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [lines, dispatch] = useReducer(reducer, []);
  const [isOpen, setIsOpen] = useState(false);
  const [ready, setReady] = useState(false);

  // Restore after mount so server and client render the same first paint.
  useEffect(() => {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (raw) dispatch({ type: "hydrate", lines: JSON.parse(raw) as CartLine[] });
    } catch {
      // Corrupt or unavailable storage: start with an empty bag.
    }
    setReady(true);
  }, []);

  useEffect(() => {
    if (!ready) return;
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(lines));
    } catch {
      // Private mode / quota: the bag is simply not persisted.
    }
  }, [lines, ready]);

  const add = useCallback(
    (product: Product, variantId: string, quantity = 1) => {
      const variant = product.variants.find((v) => v.id === variantId);
      if (!variant?.available) return;
      dispatch({
        type: "add",
        quantity,
        line: {
          slug: product.slug,
          variantId,
          title: product.title,
          variantLabel: variant.label,
          priceCents: product.priceCents,
          image: product.images[0]?.src ?? "",
        },
      });
      setIsOpen(true);
    },
    [],
  );

  const value = useMemo<CartContext>(
    () => ({
      lines,
      count: lines.reduce((n, l) => n + l.quantity, 0),
      subtotalCents: lines.reduce((n, l) => n + l.priceCents * l.quantity, 0),
      isOpen,
      open: () => setIsOpen(true),
      close: () => setIsOpen(false),
      add,
      setQuantity: (key, quantity) => dispatch({ type: "setQuantity", key, quantity }),
      remove: (key) => dispatch({ type: "remove", key }),
    }),
    [lines, isOpen, add],
  );

  return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}

export function useCart(): CartContext {
  const ctx = useContext(Ctx);
  if (!ctx) throw new Error("useCart must be used inside <CartProvider>");
  return ctx;
}
