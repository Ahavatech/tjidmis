# Finishing the reference import

`reference/articles/*.html` holds the saved article pages (abstract, keywords, DOI, affiliations). Articles without a saved page
still appear everywhere (title, authors, pages and galley come from the issue contents pages) but have no abstract.

To fetch more pages: open https://jidmis.org/index.php/jidmis in the Claude browser pane (let its bot check finish), start the small
receiver (`python tools/receiver.py`), run `tools/crawl_reference.js` in that tab's console, then:

    python tools/import_reference.py      # regenerates install/seed.sql and install/seed_demo.sql
    php install.php                       # or re-run the web installer on an empty database

The crawl keeps to one request per second.
