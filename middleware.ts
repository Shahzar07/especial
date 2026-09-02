import { NextResponse, type NextRequest } from "next/server";
import { verifyGateToken, createGateToken } from "@/lib/cookie";
import { isCrawler } from "@/lib/crawlers";
import { GATE_COOKIE, GATE_MAX_AGE } from "@/lib/config";

/**
 * Gate enforcement (spec §3).
 *
 * The matcher below excludes /gate, /api/*, /_next/*, the static metadata
 * routes, the legal pages and anything with a file extension. Everything else
 * requires a valid ml_pass cookie.
 */
export const config = {
  matcher: [
    "/((?!gate|api/|_next/|favicon|robots\\.txt|sitemap\\.xml|privacy|terms|returns|contact|.*\\.[\\w]+$).*)",
  ],
};

export async function middleware(request: NextRequest) {
  const { pathname, search, searchParams } = request.nextUrl;

  // 1. Valid cookie → straight through.
  const token = request.cookies.get(GATE_COOKIE)?.value;
  if (await verifyGateToken(token)) {
    return NextResponse.next();
  }

  // 2. ?preview=<token> → set the cookie, strip the param, continue.
  //    For QA and client demos. Unset PREVIEW_TOKEN disables the bypass
  //    entirely rather than allowing an empty match.
  const previewToken = process.env.PREVIEW_TOKEN;
  const provided = searchParams.get("preview");
  if (previewToken && provided && provided === previewToken) {
    const url = request.nextUrl.clone();
    url.searchParams.delete("preview");
    const response = NextResponse.redirect(url);
    response.cookies.set(GATE_COOKIE, await createGateToken(), {
      httpOnly: true,
      secure: process.env.NODE_ENV === "production",
      sameSite: "lax",
      maxAge: GATE_MAX_AGE,
      path: "/",
    });
    return response;
  }

  // 3. Search and social crawlers pass. Gating them would delete organic
  //    traffic and break link unfurls — see lib/crawlers.ts.
  if (isCrawler(request.headers.get("user-agent"))) {
    return NextResponse.next();
  }

  // 4. Everyone else → the gate, remembering where they were headed.
  const gate = request.nextUrl.clone();
  gate.pathname = "/gate";
  gate.search = "";
  gate.searchParams.set("next", pathname + search);
  return NextResponse.redirect(gate);
}
