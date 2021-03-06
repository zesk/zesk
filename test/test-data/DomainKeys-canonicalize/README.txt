This set of tests are intended to exercise your canonicalization
code. The test files contain content that represents statements or
actions in the DK specification. In particular, whitespace and
continuation requirements are exercised.

Each canonicalization test consists of the following files:

email.*			The original email
nofws.expected.*	The nofws canonicalized output
simple.expected.*	The simple canonicalized output
purpose.*		What this particular test is exercising

If you have a program that exercises your canonicalization routines,
then you can probably modify the run_tests script to exercise your
program and compare the results automatically.

The run_tests scripts assumes a program that takes the
canonicalization algorithm as argument one, the input file from STDIN
and writes the canonicalized text to STDOUT. It's a simple-minded
script, hack as you see fit.

Alternatively, run your canonicalization program such that it creates
a file that you can diff against the *.expected.* files.

FROM: http://domainkeys.sourceforge.net/