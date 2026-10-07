import os
import re

base = r'c:\laragon\www\Mayuri'

def fix_file(filepath, replacements):
    try:
        with open(filepath, 'rb') as f:
            content = f.read().decode('utf-8', errors='replace')
        for old, new in replacements:
            content = content.replace(old, new)
        with open(filepath, 'w', encoding='utf-8', newline='\r\n') as f:
            f.write(content)
        print(f'Fixed: {filepath}')
    except Exception as e:
        print(f'Error fixing {filepath}: {e}')

# Fix collection.php
fix_file(os.path.join(base, 'collection.php'), [
    # breadcrumb separator garbled
    ('\u00c3\u00a2\u00e2\u20ac\u201a\u00c2\u00ba Collection', '&rsaquo; Collection'),
    ('A\u00e2\u20ac\u201a\u00c2\u00baA\u00a4 Collection', '&rsaquo; Collection'),
    # search placeholder garbled
    ('Search accessories\u00c3\u00a2\u00e2\u20ac\u009a\u00c2\u00a6', 'Search accessories...'),
    ('Search accessories\u00e2\u20ac\u00a6', 'Search accessories...'),
    # Price sort arrows garbled  
    ('Price: Low \u00c3\u00a2\u00e2\u20ac \u00e2\u20ac\u2122 High', 'Price: Low &rarr; High'),
    ('Price: High \u00c3\u00a2\u00e2\u20ac \u00e2\u20ac\u2122 Low', 'Price: High &rarr; Low'),
    ('Name A\u00c3\u00a2\u00e2\u20ac\u201a\u00e2\u20ac\u201cZ', 'Name A&ndash;Z'),
    # meta separators garbled
    ('\u00c3\u201a\u00c2\u00b7 Category:', '&middot; Category:'),
    ('\u00c3\u201a\u00c2\u00b7 Search:', '&middot; Search:'),
    # No products found emoji garbled
    ('\u00c3\u00b0\u00c5\u00b8\u00e2\u20ac\u201d\u00c2', '&#128270;'),
    # wishlist heart garbled
    ("'\u00c3\u00a2\u00c2\u00c2\u00a4\u00c3\u00af\u00c2\u00b8\u00c2'", "'&#10084;&#65039;'"),
    ("'\u00c3\u00b0\u00c5\u00b8\u00c2\u00a4\u00c2'", "'&#9825;'"),
])

# Fix my_orders.php
fix_file(os.path.join(base, 'my_orders.php'), [
    # Empty orders emoji garbled
    ('\u00c3\u00b0\u00c5\u00b8\u00e2\u20ac\u00ba\u00c3\u00af\u00c2\u00b8\u00c2', '&#128722;&#65039;'),
    # phone icon garbled in delivery address  
    ('\u00c3\u00b0\u00c5\u00b8\u00e2\u20ac\u0153\u00c5\u00be', '&#128222;'),
    # Delivery Address header garbled
    ('\u00c3\u00b0\u00c5\u00b8\u00e2\u20ac\u201a\u00e2\u20ac\u0153\u00c3\u00af\u00c2\u00b8\u00c2 Delivery Address', '&#127968; Delivery Address'),
    # Cancelled order cross garbled
    ('\u00c3\u00a2\u00c2\u00c2\u00c3\u00a2\u00e2\u20ac\u00a6\u00e2\u20ac\u00a1 This order was cancelled', '&#10060; This order was cancelled'),
    # Done checkmark garbled  
    ("'\u00c3\u00a2\u00c5\u00a1\u00e2\u20ac\u0153'", "'&#10004;'"),
])

# Fix wishlist.php
fix_file(os.path.join(base, 'wishlist.php'), [
    # Empty wishlist emoji garbled
    ('\u00c3\u00b0\u00c5\u00b8\u00c2\u00a4\u00c2', '&#9825;'),
    # View link going to wrong page - fix the href
    ('href="collection.php?removed_detail=<?=$p[\'id\']?>"', 'href="product_detail.php?id=<?=$p[\'id\']?>"'),
    # img placeholder garbled
    ('\u00c3\u00a2\u00c5\u00a1\u00c2\u00a6', '&#9881;'),
])

print('All encoding fixes complete!')
