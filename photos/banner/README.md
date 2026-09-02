# Hand-made banners

Drop the finished hero artwork here and run `npm run photos`.

| File | Ratio | Used as |
|---|---|---|
| `hero.jpg` | wide, about 21:9 — 2560×1097 or larger | desktop hero |
| `hero-mobile.jpg` | portrait 4:5 — 1200×1500 or larger | mobile hero |

Anything present here wins. The pipeline only optimises and copies it to
`public/products/`, and never regenerates over it.

If a file is missing, the pipeline composes a stand-in from the product cut-outs
in `photos/source/` so the site still builds and the hero still reads correctly.
Delete the stand-in's source of truth by simply adding the real file here.

The hero is designed for **dark artwork**: the page sets the wordmark over it in
white. Keep the left third of the wide banner, and the lower third of the
portrait one, free of busy detail so the type has somewhere to sit.
