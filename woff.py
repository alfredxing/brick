#!/usr/bin/env python

import fontforge
import sys

fonts = sys.argv[1:]
for file in fonts:
    font = fontforge.open(file)
    italic = font.fullname.lower().find("italic") > -1

    name = str(font.os2_weight)
    if italic:
        name += "i"
    name += ".woff"

    font.generate(name)
