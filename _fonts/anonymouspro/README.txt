Anonymous Pro

General Information and History

Anonymous Pro is a family of four fixed-width fonts designed especially with coding in mind. Characters that could be mistaken for one another (O, 0, I, l, 1, etc.) have distinct shapes to make them easier to tell apart in the context of source code.

Anonymous Pro also features an international, Unicode-based character set, with support for most Western and European Latin-based languages, Greek, and Cyrillic. It also includes special "box drawing" characters for those who need them.

While Anonymous Pro looks great on Macs and Windows PCs with antialiasing enabled, it also includes embedded bitmaps for specific pixel sizes ("ppems" in font nerd speak) for both the regular and bold weight. (Since slanted bitmaps look pretty bad and hard to read at the supported sizes, I chose to use the upright bitmaps for the italics as well.) Bitmaps are included for these ppems: 10, 11, 12, and 13. See the usage notes below for info on what point sizes these ppems correspond to on Mac and Windows.

Anonymous Pro is based on an earlier font, Anonymous™, which was my TrueType version of Anonymous 9, a freeware Macintosh bitmap font developed in the mid-'90s by Susan Lesch and David Lamkins. The bitmap version was intended as a more legible alternative to Monaco, the fixed-width Macintosh system font.

Anonymous Pro differs from Anonymous™ and Anonymous 9 in a few key characters. While the earlier fonts had a one-story lowercase "a" like Monaco, Anonymous Pro features a two-story lowercase "a" to help distinguish it from the "o". In the earlier fonts, the slashed zero, designed to look different than the capital "O", goes the "wrong" way compared to most fonts that have this feature. Susan and David did this intentionally to distinguish it from the slashed capital "Ø" used in some languages. Some people thought this looked odd, so I put it the "right" way, and distinguish it from the "Ø" by keeping the slash inside the character.

Another significant change was to adjust the size of the characters in relation to the point size. Anonymous™ was approximately two sizes larger than comparable fonts at the same point size. This was in keeping with the old Monaco font, but can be annoying when switching between fonts. Anonymous Pro has been adjusted so that it appears about the same size as comparable fonts set at the same point size. If you have been using Anonymous™, you will need to increase the point size to get the same appearance.

Finally, unlike Anonymous™, Anonymous Pro is available in one universal TrueType format that will work on Mac OS X, Windows, and Linux. (If you're running a pre-OS X Mac, the new fonts are not compatible, but Anonymous™ will still work.)

Anonymous Pro is distributed with the Open Font License (OFL).

USAGE NOTES

I recommend disabling antialiasing for Anonymous Pro ONLY if you intend to use the sizes which have embedded bitmaps. The fonts simply don't have the kind of high-quality TrueType hints needed for them to display well at other sizes without antialiasing. Here are some OS-specific recommendations:

MacOS: 

Anonymous Pro will display using Quartz antialiasing unless you disable it system-wide in the Appearance panel or in specific apps that allow it (BBEdit, TextMate, Terminal, Coda, etc.). Bitmaps will be used at the following point sizes: 10, 11, 12, and 13. Note that the 13-point bitmaps are not available when using the system-wide antialiasing suppression, which only works for 12-point text or smaller.

If you use TextMate, be sure to use version 1.5.8 (1505) or later. There was a bug which misinterpreted the line height when embedded bitmaps are present in a TTF font. (Thanks, Allan, for fixing this!)

Windows: 

It's best to enable "font smoothing" (Control Panel > Display Properties > Appearance > Effects...). When font smoothing is set to "Standard", the embedded bitmaps will automatically be used for the following point sizes: 7, 8, 9, and 10. For other sizes, or if you prefer non-jagged type, "ClearType" is the best choice.

Linux:

From all reports, Anonymous Pro displays well on Linux systems. Here are installation instructions, kindly provided by a Linux user:

Copy the *.ttf files to a font directory such as
~/.fonts or /usr/share/fonts/ttf. The exact location depends on your
distribution. See /etc/fonts/fonts.conf for details if unsure.

Run fc-cache using the command: 'sudo fc-cache -f'


OTHER INFORMATION

See "FONTLOG for Anonymous Pro.txt" for the changelog, credits, etc.

Mark Simonson
September 8, 2010
Mark Simonson Studio LLC 
http://www.ms-studio.com 
mark@marksimonson.com

