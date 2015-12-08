#!/usr/bin/env ruby

require "fileutils"

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

# The "proof" dir. This will be populated with empty files that prove the
# template exists. Necessary due to jekyll's restrictions
proof_dir = "assets/charmap_template"

# How many columns the table should have
cols = 10

## EDIT END





# Make sure dirs ends in slash
output_dir << '/' unless output_dir.end_with?('/')
proof_dir << '/' unless proof_dir.end_with?('/')

# clear proof files before we start
FileUtils.rm_rf( Dir.glob("#{proof_dir}/*") )

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

	# Get the font name from the index.html file (ex: `Alegreya Sans`)
	font_name = File
		.read( File.dirname( charmap ) + '/index.html' )
		.scan( /(?<=family: )(.*.)/ )[0][0]

	# Get charmap name without extension (ex: `400i`)
	charmap_name = File.basename( charmap, File.extname( charmap ) )

	# Append charmap name with `.html` for use as template name
	template_name = "#{charmap_name}.html"

	# Join `output_dir` and `font_name`, with whitespaces removed
	template_dir = "#{output_dir}#{font_name.gsub(/\s+/, "")}/"


	html = make_html( File.read( charmap ), cols )

	# Create directory if it doesn't exist
	Dir.mkdir( template_dir ) unless File.exists?( template_dir )

	# Create new template file
	template = File.open( template_dir + "/" + template_name, "w" );

	# Write the HTML
	template << html

	# Close file
	template.close

	# Create an empty file in the charmap dir that proves it exists
	it_exists = File.open( "#{proof_dir}#{font_name.gsub(/\s+/, '')}-#{charmap_name}" , "w" );
end

# And we're done!
stopwatch_finish = Time.now
stopwatch_delta = stopwatch_finish - stopwatch_start

puts "Finished in #{stopwatch_delta}s"
