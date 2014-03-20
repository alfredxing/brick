# EB Garamond
## Claude Garamont’s designs go opensource.

This project aims at providing a free version of the Garamond types, based on the Designs of the Berner specimen from 1592.

In the end, the fonts shall cover extended latin, greek and cyrillic scripts in different styles (regular, italic, bold, bolditalic) and design sizes. There are also fonts containing initials based on those found in a 16th century french bible print. The fonts make heavy use of opentype features for specialities like small caps or different number styles as well as for imitating renaissance typography.

For the use with Xe- and LuaLaTeX I’m working on a configuration for mycrotype. For the use on the web via @fontface, the make-script produces eot and woff files which can be found in the web section. But be aware that they are not subset but contain the whole fonts, which might result in undesirably big files. Webfont hosters like googlefonts or fontsquirrel might provide better solutions.

## Fonts in this repository:

- EBGaramond12-Regular: Regular font for design size 12pt
- EBGaramond12-Italic: Italic font for design size 12pt
- EBGaramond12-Bold: Bold font for design size 12pt (very rough/unusable; not included in releases)
- EBGaramond08-Regular: Regular font for design size 8pt
- EBGaramond08-Italic: Italic font for design size 8pt (very rough spacing!)
- EBGaramond12-SC: Smallcaps font for programs that ignore opentype features (12pt)
- EBGaramond12-AllSC: All smallcaps font for programs that ignore opentype features
- EBGaramond08-SC: Smallcaps font for programs that ignore opentype features (8pt)
- EBGaramond-Initials: Initials
- EBGaramond-InitialsF1: Background (the ornament) of initials
- EBGaramond-InitialsF2: Foreground (the letter) of initials
- EBGaramond-Lettrines: Workbench for Initials fonts (not included in releases)

This is a work in progress, so expect bugs! The qualitiy of the fonts still varies widely! You can see every font’s current state in its *-Glyphs.pdf file in the specimen section.

For more infos please visit http://www.georgduffner.at/ebgaramond/
