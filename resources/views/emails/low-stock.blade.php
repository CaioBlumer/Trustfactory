<h1>Low stock alert</h1>
<p>
    The following product is running low on stock:
</p>
<ul>
    <li><strong>Product:</strong> {{ $product->name }}</li>
    <li><strong>Remaining stock:</strong> {{ $product->stock_quantity }}</li>
    <li><strong>Threshold:</strong> {{ $threshold }}</li>
</ul>
