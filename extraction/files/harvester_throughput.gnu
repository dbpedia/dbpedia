set title "DBpedia Harvester Throughput"

set style data histogram
set style histogram rowstacked
set style fill solid border -1


set key outside bottom
set boxwidth 1.0
set terminal png size 800, 200
set output "files/statistic/harvester_throughput.png"

set xrange [0:]

set xlabel "Each bar represents 10 minutes" 
set ylabel "Number of page updates" 

plot 'files/harvester_throughput.dat' using 1 title "accepted" lt  rgbcolor "blue" , '' using 2 title "rejected" lt  rgbcolor "red"
