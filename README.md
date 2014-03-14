### [Brick.](http://brick.im) Webfonts that actually look good.

In the age of the Internet, we've found ourselves in yet another typographic battle. In an effort to speed up loading times, we've compressed fonts, and along the way, we've lost the majority of the quality of rendered type.

Let's change that. The fonts served by Brick are clones of the original, converted without modification to serveral formats for wider browser compatibility.

All fonts are served as WOFF-conpressed versions of the originals&mdash;no quality lost.

### Usage

Okay, let's get started. I don't want to overload the GitHub servers, so the font serving takes place another server. The loading itself is quite simple; kind of like using Google Web Fonts (but with more customization options—we'll get to that later).

Suppose you want to use a font that's in the catalog, for example, TeX Gyre Heros, and only in regular weight.  
The stylesheet URL would be structured like this:  
````
//brick.a.ssl.fastly.net/TeX+Gyre+Heros:400
````
And if you also want to load EB Garamond regular and italic, you can do that too:
````
//brick.a.ssl.fastly.net/TeX+Gyre+Heros:400/EB+Garamond:400,400i
````

### Is it actually better?

You can see for yourself (best comparison is in Chrome; Firefox seems to render all fonts pretty well, though the difference is still clearly visible):

- [Brick](http://brick.im/preview/brick.html)
- [Google Fonts](http://brick.im/preview/google.html)

Take note of:

- Font rendering and smoothing
- Ligatures: the `fi` ligature in the title
- Kerning: the `Ve` pair in `Vestibulum`
- Character sets: although not demonstrated in the previews, Brick fonts include the entire character set that came with the original
