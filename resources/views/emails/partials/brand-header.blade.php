<tr>
    <td align="center" style="background:#ffffff;padding:22px 24px 16px;text-align:center;">
        @php
            $logoPath = public_path('images/logos/nebula.png');
            $logoSrc = isset($message) && is_object($message) && method_exists($message, 'embed') && is_file($logoPath)
                ? $message->embed($logoPath)
                : url('images/logos/nebula.png');
        @endphp
        <img src="{{ $logoSrc }}" alt="Nebula Institute of Technology" width="180" border="0"
            style="max-width:180px;width:180px;height:auto;display:inline-block;border:0;outline:none;text-decoration:none;">
    </td>
</tr>
