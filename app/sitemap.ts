import type { MetadataRoute } from "next";
import { categories, site } from "@/lib/config";
import { getAllProducts } from "@/data/products";

export default function sitemap(): MetadataRoute.Sitemap {
  const base = site.domain;

  return [
    { url: base, changeFrequency: "weekly", priority: 1 },
    ...categories.map((c) => ({
      url: `${base}/${c.slug}`,
      changeFrequency: "weekly" as const,
      priority: 0.8,
    })),
    ...getAllProducts().map((p) => ({
      url: `${base}/product/${p.slug}`,
      lastModified: new Date(p.releasedAt),
      changeFrequency: "weekly" as const,
      priority: 0.7,
    })),
    ...["privacy", "terms", "returns", "contact"].map((path) => ({
      url: `${base}/${path}`,
      changeFrequency: "yearly" as const,
      priority: 0.3,
    })),
  ];
}
