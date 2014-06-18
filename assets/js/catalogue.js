var bucket = {};

// forEach polyfill for NodeList
NodeList.prototype.forEach=function(c,d){var e,a;if(null==this)throw new TypeError(" this is null or not defined");var b=Object(this),g=b.length>>>0;if("function"!==typeof c)throw new TypeError(c+" is not a function");d&&(e=d);for(a=0;a<g;){var f;a in b&&(f=b[a],c.call(e,f,a,b));a++}}
// Object.keys polyfill for weird people still using IE7/8
Object.keys||(Object.keys=function(b){var c=[],a;for(a in b)Object.prototype.hasOwnProperty.call(b,a)&&c.push(a);return c});

// Filters
function filter(field, value) {
    document.getElementById("fonts").querySelectorAll(".font").forEach(function(el) {
        if (!value) {
            el.className = "font";
        } else if (el.getAttribute("data-" + field) !== value) {
            el.className = "font hidden";
        } else {
            el.className = "font";
        }
    });
    document.getElementById("fonts").querySelectorAll(".font:not(.hidden)").forEach(function(el, i) {
        if (i % 5 == 4)
            el.className = "font row-end";
    });
}

function updateBucket() {
    var newList = "",
        code = "<link rel=\"stylesheet\" href=\"\/\/brick.a.ssl.fastly.net",
        size = Object.keys(bucket).length;

    document.getElementById("bucket-num").innerText = size + ((size === 1) ? " font" : " fonts");
    document.getElementById("bucket-list").setAttribute("data-count", size);

    if (size === 0) {
        document.getElementById("bucket").className = "hidden";
        return;
    }

    // Formulate new DOM values
    for (var i in bucket) {
        var styles = bucket[i];

        newList += "<li><h3>" + i + "</h3><div class=\"weights\">";
        code += "/" + i.replace(" ", "+") + ":";
        
        for (var j in styles) {
            newList += "<span data-font=\"" + i + "\""
            if (styles[j]) {
                newList += " data-in";
                code += j + ",";
            }
            newList += ">" + j + "</span>";
        }

        newList += "</li>";
        code = code.replace(/,$|\/[\w\+]+:$/, "");
    }

    // Replace necessary DOM element values
    document.getElementById("bucket").querySelector("ul").innerHTML = newList;
    document.getElementById("code").innerText = (code.substr(-4) === ".net") ? "" : code + "\">";
}

// Event listeners

document.getElementById("filters").onclick = function(e) {
    if (!e.target.className.match(/option/)) return;
    (function () {
        if (this.className === "option active") {
            this.className = "option";
            filter(this.getAttribute("data-field"), false);
        } else {
            this.parentNode.childNodes.forEach(function(el) {
                el.className = "option";
            });
            this.className = "option active";
            filter(this.getAttribute("data-field"), this.getAttribute("data-option"));
        }
    }).call(e.target);
}

document.getElementById("fonts").onclick = function(e) {
    if (!e.target.className.match(/add/)) return;
    (function () {
        if (this.className === "add added") {
            delete bucket[this.getAttribute("data-font")];
            updateBucket();
            this.className = "add";
            this.innerText = "+";
        } else {
            var styles = this.getAttribute("data-styles").split(",");
            var font = bucket[this.getAttribute("data-font")] = {};
            for (var i = 0; i < styles.length; i++) {
                font[styles[i]] = (styles[i] === "400"); 
            }
            updateBucket();
            this.className = "add added";
            this.innerText = "-";
        }
    }).call(e.target);
}

document.getElementById("bucket").onclick = function(e) {
    if (e.target.tagName.toLowerCase() !== "span") return;
    (function () {
            var weight = this.innerText, font = this.getAttribute("data-font");
        if (this.getAttribute("data-in") !== null) {
            bucket[font][weight] = false;
        } else {
            bucket[font][weight] = true;
        }
        updateBucket();
    }).call(e.target);
}

document.getElementById("bucket-list").onclick = function() {
    var bucketElement = document.getElementById("bucket");
    if (bucketElement.className === "hidden" && Object.keys(bucket).length > 0) {
        bucketElement.className = "";
    } else {
        bucketElement.className = "hidden";
    }
}

document.getElementById("code").onclick = function() {
    this.select();
    this.onmouseup = function() {
        this.onmouseup = null;
        return false;
    };
};

// Scroll event
window.onscroll = function() {
    var picker = document.getElementById("picker");
    if (window.scrollY > 80) {
        picker.className = "fixed";
    } else {
        picker.className = "";
    }
}

// Initial row formatting
filter("class", false);