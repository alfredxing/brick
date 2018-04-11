package main

import (
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"
)

// Tests a request to Brick with an invalid root request (/)
func TestRoot(t *testing.T) {
	res := httptest.NewRecorder()

	req, err := http.NewRequest("GET", "http://brick.im/", nil)
	if err != nil {
		t.Fatal(err)
	}

	handler(res, req)
	if res.Code != 400 || len(res.Body.String()) > 0 {
		t.Fail()
	}
}

// Tests a request for a single font
func TestSingle(t *testing.T) {
	res := httptest.NewRecorder()

	req, err := http.NewRequest("GET", "http://brick.im/EB+Garamond:400", nil)
	if err != nil {
		t.Fatal(err)
	}

	handler(res, req)
	if res.Code != 200 {
		t.Fail()
	}

	var expected = formatRule("EB Garamond", "normal", "400", "EB Garamond 12 Regular", "ebgaramond", "400")
	if res.Body.String() != expected {
		t.Fail()
	}
}

// Tests a request for multiple weights and styles of a font
func TestVariants(t *testing.T) {
	res := httptest.NewRecorder()

	req, err := http.NewRequest("GET", "http://brick.im/Raleway:400,400i,700", nil)
	if err != nil {
		t.Fatal(err)
	}

	handler(res, req)
	if res.Code != 200 {
		t.Fail()
	}

	var expected = formatRule("Raleway", "normal", "400", "Raleway Regular", "raleway", "400") +
		formatRule("Raleway", "italic", "400", "Raleway Italic", "raleway", "400i") +
		formatRule("Raleway", "normal", "700", "Raleway Bold", "raleway", "700")
	if res.Body.String() != expected {
		t.Fail()
	}
}

// Tests a request for multiple font families
func TestFamilies(t *testing.T) {
	res := httptest.NewRecorder()

	req, err := http.NewRequest("GET", "http://brick.im/Open+Sans:400,700/Merriweather:400", nil)
	if err != nil {
		t.Fatal(err)
	}

	handler(res, req)
	if res.Code != 200 {
		t.Fail()
	}

	var expected = formatRule("Open Sans", "normal", "400", "Open Sans Regular", "opensans", "400") +
		formatRule("Open Sans", "normal", "700", "Open Sans Bold", "opensans", "700") +
		formatRule("Merriweather", "normal", "400", "Merriweather", "merriweather", "400")
	if res.Body.String() != expected {
		t.Fail()
	}
}

// Tests a request including non-existing variants
func TestNonexistentVariant(t *testing.T) {
	res := httptest.NewRecorder()

	req, err := http.NewRequest("GET", "http://brick.im/Open+Sans:400,800", nil)
	if err != nil {
		t.Fatal(err)
	}

	handler(res, req)
	if res.Code != 200 {
		t.Fail()
	}

	var expected = formatRule("Open Sans", "normal", "400", "Open Sans Regular", "opensans", "400")
	if res.Body.String() != expected {
		t.Fail()
	}
}

// Tests a request including non-existing families
func TestNonexistentFamily(t *testing.T) {
	res := httptest.NewRecorder()

	req, err := http.NewRequest("GET", "http://brick.im/Foo+Sans:400/Open+Sans:400", nil)
	if err != nil {
		t.Fatal(err)
	}

	handler(res, req)
	if res.Code != 200 {
		t.Fail()
	}

	var expected = formatRule("Open Sans", "normal", "400", "Open Sans Regular", "opensans", "400")
	if res.Body.String() != expected {
		t.Fail()
	}
}

// Tests a request using the force flag (preventing the browser from
// loading the font from the local computer)
func TestForce(t *testing.T) {
	res := httptest.NewRecorder()

	req, err := http.NewRequest("GET", "http://brick.im/Roboto:400:f", nil)
	if err != nil {
		t.Fatal(err)
	}

	handler(res, req)
	if res.Code != 200 {
		t.Fail()
	}

	var expected = "@font-face{font-family:'Roboto';font-style:normal;font-weight:400;src:url(//brick.freetls.fastly.net/fonts/roboto/400.woff) format('woff')}"
	if res.Body.String() != expected {
		t.Fail()
	}
}

// Templating function to build the expected CSS @font-face rules
func formatRule(family string, style string, weight string, local string, slug string, file string) string {
	template := "@font-face{font-family:'%s';font-style:%s;font-weight:%s;" +
		"src:local('%s'),url(//brick.freetls.fastly.net/fonts/%s/%s.woff) format('woff')}"
	return fmt.Sprintf(template, family, style, weight, local, slug, file)
}
