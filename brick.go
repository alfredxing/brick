---
---

// Brick. Webfonts that actually look good

package main

import (
	"bytes"
	"fmt"
	"net/http"
	"strings"
	"time"
)

// Handles an incoming CSS request, and builds CSS based on query parameters
// (font family, weights and styles, flags)
func handler(w http.ResponseWriter, r *http.Request) {
	{% assign fonts = site.fonts %}
	var fonts = map[string](map[string]string) {
		{% for font in fonts %}
		"{{ font.family }}": map[string]string {
			{% for style in font.styles %}
			"{{ style[0] }}": "{{ style[1] }}",
			{% endfor %}
		},
		{% endfor %}
	}

	w.Header().Set("Content-type", "text/css")
	w.Header().Set("X-Content-Type-Options", "nosniff")
	w.Header().Set("Cache-Control", "public, max-age=2628000")
	w.Header().Set("Expires", time.Now().Add(2628000*time.Second).Format(time.RFC1123))
	w.Header().Set("Last-Modified", "{{ site.time | date: '%a, %d %b %Y %T %Z'}}")
	w.Header().Set("Pragma", "Public")
	w.Header().Set("Server", "Brick")

	query := strings.Split(strings.Trim(r.URL.Path, "/"), "/")

	if len(query) == 0 || query[0] == "" {
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	for _, font := range query {
		data := strings.Split(font, ":")
		if len(data) < 2 {
			w.WriteHeader(http.StatusBadRequest)
			return
		}

		family := strings.Replace(data[0], "+", " ", -1)
		weights := strings.Split(data[1], ",")

		// Flags
		flags := ""
		if len(data) > 2 {
			flags = data[2]
		}

		for _, weight := range weights {
			// Verify that variant exists, else move on
			if _, exists := fonts[family][weight]; !exists {
				continue
			}

			var uri bytes.Buffer

			// Local font URI
			if !strings.Contains(flags, "f") {
				local := fonts[family][weight]
				uri.WriteString("local('")
				uri.WriteString(local)
				uri.WriteString("'),")
			}

			// Brick URI
			uri.WriteString("url(//brick.a.ssl.fastly.net/fonts/")
			uri.WriteString(strings.ToLower(strings.Replace(family, " ", "", -1)))
			uri.WriteString("/")
			uri.WriteString(weight)
			uri.WriteString(".woff) format('woff')")

			// Real weight and style
			realWeight := strings.TrimSuffix(weight, "i")
			style := "normal"
			if len(realWeight) < len(weight) {
				style = "italic"
			}

			template := "@font-face{font-family:'%s';font-style:%s;font-weight:%s;src:%s}"
			fmt.Fprintf(w, template, family, style, realWeight, uri.String())
		}
	}
}

// Starts an HTTP server to listen for incoming requests
func main() {
	http.HandleFunc("/", handler)
	http.ListenAndServe(":9811", nil)
}
