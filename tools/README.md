# Corpus tools

Here you'll find any scripts I write to extract or manipulate a corpus made with this system.

## Extract stories for a range of keywords

**stories-for-keywords.php** is a command line script that takes a list of keywords as an arcument and returns a CSV file list of stories containing 3 or more of them to stdout.

**usage:**  php ./stories-for-keywords.php [comma delinired list of keywords]
**example:** php ./stories-for-keywords.php foo,bar,baz

**returns:** the contents of a CSV file to stdout, with 2 columns: URL, and text.

You can pipe this with the > character to a file if you wish.

**Configuration:**
You'll need to edit line 30 as follows to point the script at your corpus directory

$path_to_corpus = '/full/path/to/corpus'; // directory where the processed corpus lives
