### [Brick.](http://brick.im) Webfonts that actually look good.

In the age of the Internet, we've found ourselves in yet another typographic battle. In an effort to speed up loading times, we've compressed fonts, and along the way, we've lost the majority of the quality of rendered type.

Let's change that. The fonts served by Brick are clones of the original, converted without modification to serveral formats for wider browser compatibility.

There's OTF, which are rendered beautifully by Google Chrome and Firefox, and WOFF, which is supported by modern versions of Internet Explorer. And then there's SVG (disabled by default), if you really need Chrome to render the fonts perfectly (but without proper kerning or ligatures).

### Usage

Okay, let's get started. I don't want to overload the GitHub servers, so the font serving takes place another server. The loading itself is quite simple; kind of like using Google Web Fonts (but with more customization options—we'll get to that later).

Suppose you want to use a font that's in the catalog, for example, TeX Gyre Heros, and only in regular weight.  
The stylesheet URL would be structured like this:  
````
//get.brick.im/TeX+Gyre+Heros:400
````
And if you also want to load EB Garamond regular and italic, you can do that too:
````
//get.brick.im/TeX+Gyre+Heros:400/EB+Garamond:400,400i
````
