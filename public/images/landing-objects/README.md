# Department object images

The nine PNGs in this folder are the object images supplied from `C:/Downloads`, installed on October 1, 2026. They replace the original empty placeholders. The source files in Downloads are preserved.

Replace each file with a high-quality, transparent photographic cutout using the same filename. Use tightly cropped objects with at least 1000 pixels along the longest edge. Do not include white backgrounds, captions, or multiple products in one image. The browser preserves each object's proportions with `object-fit: contain`.

| Team | File | Placement |
| --- | --- | --- |
| Mighty Sea Dragons | `marine-biology/microscope.png` | Upper left, soft background layer |
| Mighty Sea Dragons | `marine-biology/coral.png` | Right edge, foreground layer |
| Mighty Sea Dragons | `marine-biology/diving-goggles.png` | Lower left, softly masked |
| Terraquatic Eagles | `fisheries-agriculture/rice-stalks.png` | Upper left, soft background layer |
| Terraquatic Eagles | `fisheries-agriculture/fish.png` | Right edge, foreground layer |
| Terraquatic Eagles | `fisheries-agriculture/tractor.png` | Lower left, softly masked |
| Trojan Warriors | `information-technology/motherboard.png` | Upper left, soft background layer |
| Trojan Warriors | `information-technology/mouse.png` | Right edge, foreground layer |
| Trojan Warriors | `information-technology/keyboard.png` | Lower left, softly masked |

To use WebP or another local filename, update the corresponding team's `objects` entry in `config/landing.php`. Paths are relative to `public/`. Missing files are skipped rather than showing broken images. Replacing an image at the existing path requires only a browser refresh; CSS changes require `npm run build`. If Laravel configuration is cached after changing paths, run `php artisan config:clear`.

`resources/css/landing.css` controls the three placements, 28–35% opacity, department tint, blur, shadow, and gradient masks. The objects sit inside the logo row, are partly cropped by the fabric, and fade out around the clear center. Headings and scores are outside this decorative layer. The same central mask adapts to mobile banners.

Keep each team's actual logo in its separate `image` setting in `config/landing.php`. These object slots never replace that logo or its TEAM LOGO placeholder.

Scores display the latest active-edition team tallies when a Tabulator has submitted them. Before any tally is declared, teams show `0` points and unset ranks.
