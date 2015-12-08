#!/usr/bin/env ruby

# Cleanup charmaps

## START

# Time how long it generates
stopwatch_start = Time.now

# Get files
files = Dir.glob( '_fonts/*/*.charmap' )

# Now we loop
files.each do |charmap|
	puts "Cleaning #{charmap}"

	lines = File.read(charmap)

	# Remove duplicate lines,
	# Pad maximum zeroes 4 digits,
	# Sort by alphabetical order
	# Delete control characters 00 - 1f,
	lines = lines
				.split( "\n" )
				.uniq
				.map{ |n| n.rjust( 4, "0" ) }
				.sort_by{ |l| l.downcase }
				.slice( 31..-1 )
				.join( "\n" )

	output = File.open( charmap, "w" )
	output.truncate(0)
	output << lines
	output.close
end

# And we're done!
stopwatch_finish = Time.now
stopwatch_delta = stopwatch_finish - stopwatch_start

puts "Finished in #{stopwatch_delta}s"
