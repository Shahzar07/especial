/**
 * Crawler allowlist.
 *
 * Spec §3: a hard gate on every route destroys organic traffic and kills link
 * previews. Search and social crawlers are let through to the real page so the
 * editorial block on the home page can actually rank and links unfurl.
 *
 * Note this is User-Agent matching, which is trivially spoofable — that is an
 * accepted trade. The gate is a mailing-list capture, not an access control;
 * nothing behind it is secret. Never put anything sensitive behind it.
 */
const CRAWLER_UA = [
  "googlebot",
  "google-inspectiontool",
  "storebot-google",
  "bingbot",
  "slurp",
  "duckduckbot",
  "baiduspider",
  "yandexbot",
  "applebot",
  "facebookexternalhit",
  "facebot",
  "twitterbot",
  "linkedinbot",
  "pinterest",
  "slackbot",
  "discordbot",
  "whatsapp",
  "telegrambot",
  "redditbot",
  "embedly",
  "quora link preview",
  "gptbot",
  "oai-searchbot",
  "chatgpt-user",
  "perplexitybot",
  "claudebot",
  "ia_archiver",
  "lighthouse",
  "chrome-lighthouse",
];

export function isCrawler(userAgent: string | null): boolean {
  if (!userAgent) return false;
  const ua = userAgent.toLowerCase();
  return CRAWLER_UA.some((bot) => ua.includes(bot));
}
