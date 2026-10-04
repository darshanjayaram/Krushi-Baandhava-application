# Onboarding Tour Audio

Optional pre-recorded narration for the "How to Use" tour
(`resources/views/components/onboarding-tour.blade.php`).

```
public/audio/tour/
├── kn/step1.mp3 … step5.mp3   (Kannada narration)
└── en/step1.mp3 … step5.mp3   (English narration)
```

| Step | Target | Content |
|---|---|---|
| 1 | Location card / header pill | Set your village / district |
| 2 | Language toggle | Switch Kannada ⇄ English |
| 3 | Today's market rates section | Official mandi rates + search |
| 4 | First crop card | Tap a crop for trends / where & when to sell |
| 5 | Mobile bottom nav (mobile only) | Rates, schemes, weather, videos |

**Status:** all 10 files are bundled (generated with Google's Kannada / English
speech voice). Phones don't need a Kannada voice installed — the MP3 is played
directly. To improve quality, replace any file with a human recording using
the same name (mono, 64–96 kbps, < 10 s).

**Fallback:** if an MP3 fails to load, the browser's Web Speech API is used.
Kannada mode always requests a Kannada (`kn-IN`) voice and never switches to
English.
