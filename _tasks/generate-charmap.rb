#!/usr/bin/env ruby

# Generate character maps

# Make a .ttx file containing only the CMAP
def make_cmap_ttx( name, source )
	# puts "ttx -f -o #{name} -t cmap #{source}"
	system *%W(ttx -f -o #{name} -t cmap #{source})

	# Check if it worked
	if ! File.file?( name )
		raise "Failed to create ttx cmap `#{name}`"
		exit
	end
end

# Return all hexadecimal values starting with `0x` without including `0x`
def grab_hex( str )
	return str.scan( /(?<=0[xX])([0-9a-fA-F]+)/ )
end



## START

# Time how long it generates
stopwatch_start = Time.now

# Get files
#files = Dir.glob( '_fonts/*/400.woff' )
files = Dir.glob( '_fonts/*/*.woff' )

# The resulting extension
ext = ".charmap"

# Now we loop
files.each do |font|

	font_name = File.basename( font )
	font_base_name = File.basename( font, ".*" )

	# Make a temporary .ttx file containing only the cmap
	temp_name = File.dirname( font ) + "__#{Time.now.to_i.to_s}.cmap.ttx"
	make_cmap_ttx( temp_name, font )

	# Grab the temp file's contents
	temp = File.read( temp_name )

	# Get the Unicode values from the XML file
	# Assumptions: UTF strings begin with '0x'
	characters = grab_hex( temp )

	# delete temp file
	File.delete( temp_name )

	# Make an output file with the same dir as font file
	#output = File.open( font + ext, "w" );
	output = File.open( "#{File.dirname( font )}/#{font_base_name}#{ext}", "w" );
	output.truncate(0)

	# Output the unicode values line-by-line
	output << characters.join( "\n" )
	output.close
end

# And we're done!
stopwatch_finish = Time.now
stopwatch_delta = stopwatch_finish - stopwatch_start

puts "Finished in #{stopwatch_delta}s"
