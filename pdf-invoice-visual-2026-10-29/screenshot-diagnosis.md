# Screenshot diagnosis and proven capture setups

Executed 2026-10-29 (Asia/Dhaka) using isolated `shot-diag-*` sessions. No WordPress, plugin, global CLI, browser preference, shared `office-agent` profile or installation changes were made. All diagnostic sessions were closed.

The earlier failure is not reproducible in a clean default session at this time. Agent-browser 0.27.3 with cached Chrome for Testing 146.0.7680.153 captured four PNGs successfully: blank viewport, nonblank viewport, full page and repeated viewport. The 1280 x 1595 full image was visually inspected and contains the expected heading, long blue section and end text. Default viewport PNGs are 1280 x 577.

```sh
agent-browser --session shot-diag-base open about:blank
agent-browser --session shot-diag-base screenshot /tmp/shot-diag-base.png
agent-browser --session shot-diag-base screenshot --full /tmp/shot-diag-full.png
```

The nonblank/full diagnostics used a simple data URL, independent of the application under test. Source and capture artifacts remain under `/tmp/shot-diag-*`.

## Per-session capture flag

The following documented browser-launch option was tested without changing global configuration:

```sh
agent-browser --session shot-diag-surface \
  --args '--enable-features=CDPScreenshotNewSurface' open about:blank
```

Six separate nonblank data-page navigations each produced a viewport and full PNG, for twelve successful captures. Navigation plus both captures took 0.107, 0.113, 0.117, 0.120, 0.099 and 0.101 seconds. Final full PNG `/tmp/shot-diag-surface-6-full.png` measures 1280 x 1316 and was visually inspected. This establishes bounded repeatability; the unflagged clean session also worked, so these observations alone do not prove that this flag repaired the original intermittent fault.

## Existing alternate browser

The installed Microsoft Edge 156.0.4314.50 also captured a blank page and a nonblank full page successfully through the same agent-browser CLI:

```sh
agent-browser --session shot-diag-edge \
  --executable-path '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge' \
  open about:blank
agent-browser --session shot-diag-edge screenshot --full /tmp/shot-diag-edge-full.png
```

Its full diagnostic image measures 1272 x 1416. The root agent selected this explicit per-session executable for the actual invoice and native PDF-viewer visual checks. No user browser default or shared profile was changed.

## Upstream evidence and limits

[Upstream issue 1437](https://github.com/vercel-labs/agent-browser/issues/1437) reports the same macOS pattern: navigation and accessibility work while screenshot capture times out and can block subsequent daemon commands. [Upstream PR 1189](https://github.com/vercel-labs/agent-browser/pull/1189) investigates Chrome compositor capture behavior, describes the `CDPScreenshotNewSurface` feature and proposes a headless-shell runtime/focus preparation change. Both primary sources were read during this run. Their stated affected Chrome builds are 147/149, while this machine's cached build is 146; a shared cause for the earlier local fault remains an inference. No package update or root-cause patch is claimed from fresh-session recovery alone.

The actual application screenshots, native PDF display checks and core/Pro state restoration are owned by the root agent and documented separately in this cycle's report.

Preserved unmodified diagnostic images: `evidence/diagnostic-default-chrome-full.png`, `evidence/diagnostic-surface-stress-full.png` and `evidence/diagnostic-edge-full.png`. Default and surface-stress samples were visually inspected; the Edge sample is available for root's review.
