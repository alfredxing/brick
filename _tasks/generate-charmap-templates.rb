#!/usr/bin/env ruby

# Generate character map templates. This will be used by Jekyll to build the
# character maps

## EDIT START

# Time how long it generates
stopwatch_start = Time.now

# Get files
files = Dir.glob( '_fonts/*/*.charmap' )

# The resulting extension
ext = ".charmap"

# The dir to output to
output_dir = "_includes/charmaps"

# How many columns the table should have
cols = 10

## EDIT END





# Make sure output dir ends in slash
output_dir << '/' unless output_dir.end_with?('/')

# Generate the html
def make_html( charmap, columns )
	html = "<table>\n"
	current_col = 0

	# loop through each line
	# source: http://stackoverflow.com/a/601927
	charmap.split(/\r?\n|\r/).each do |line|

		if ( current_col % columns == 0 )
			html += "\t<tr>\n"
		end

		html += "\t\t<td>&#x" + line + ";</td>\n"

		if ( current_col % columns == columns - 1 )
			html += "\t</tr>\n"
		end

		current_col += 1
	end

	if ( current_col % columns != 0 )
		html += "\t</tr>\n"
	end

	html += "</table>"

	return html
end

# Now, the main loop
files.each do |charmap|
	puts "Generating template for #{charmap}"

	#font_name = File.basename( File.dirname( charmap ) )
	# Get the font name from the index.html file
	font_name = File
		.read( File.dirname( charmap ) + '/index.html' )
		.scan( /(?<=family: )(.*.)/ )[0][0]
	charmap_style = File.basename( charmap, File.extname( charmap ) )
	template_name = "#{charmap_style}.html"
	template_dir = "#{output_dir}#{font_name}/"

	html = make_html( File.read( charmap ), cols )

	Dir.mkdir( template_dir ) unless File.exists?( template_dir )

	template = File.open( template_dir + "/" + template_name, "w" );

	# Write the HTML
	template << html

	template.close
end

# And we're done!
stopwatch_finish = Time.now
stopwatch_delta = stopwatch_finish - stopwatch_start

puts "Finished in #{stopwatch_delta}s"
