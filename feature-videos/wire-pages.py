#!/usr/bin/env python3
"""Swap each feature page's hero VideoPlaceholder for FeatureVideo.

Usage: python3 wire-pages.py <slug>=<m:ss> [<slug>=<m:ss> ...]
Keeps the placeholder's title, adds the asset + duration, fixes imports.
"""
import re
import sys
from pathlib import Path

WEB = Path(__file__).resolve().parents[2] / 'web-content'

for arg in sys.argv[1:]:
    slug, _, duration = arg.partition('=')
    page = WEB / 'src/pages/features' / f'{slug}.astro'
    s = page.read_text()
    m = re.search(r'<VideoPlaceholder slot="media" title="([^"]+)"[^/]*/>', s)
    if not m:
        if f"asset('tours/{slug}')" in s:
            s = re.sub(r"(<FeatureVideo slot=\"media\" media=\{asset\('tours/%s'\)\} title=\"[^\"]+\")( duration=\"[^\"]*\")?" % re.escape(slug),
                       lambda mm: f'{mm.group(1)} duration="{duration}"', s)
            page.write_text(s)
            print(f'{slug}: duration updated to {duration}')
        else:
            print(f'{slug}: no hero placeholder found, skipped')
        continue
    title = m.group(1)
    s = s.replace(m.group(0), f'<FeatureVideo slot="media" media={{asset(\'tours/{slug}\')}} title="{title}" duration="{duration}" />')
    # imports
    if 'VideoPlaceholder' not in s.replace("import VideoPlaceholder from '../../components/ui/VideoPlaceholder';", ''):
        s = s.replace("import VideoPlaceholder from '../../components/ui/VideoPlaceholder';", "import FeatureVideo from '../../components/ui/FeatureVideo';")
    elif "components/ui/FeatureVideo'" not in s:
        s = s.replace("import VideoPlaceholder from '../../components/ui/VideoPlaceholder';",
                      "import VideoPlaceholder from '../../components/ui/VideoPlaceholder';\nimport FeatureVideo from '../../components/ui/FeatureVideo';")
    if "from '../../data/product/assets'" not in s:
        s = s.replace("import FeatureVideo from '../../components/ui/FeatureVideo';",
                      "import FeatureVideo from '../../components/ui/FeatureVideo';\nimport { asset } from '../../data/product/assets';")
    page.write_text(s)
    print(f'{slug}: wired ({title}, {duration})')
