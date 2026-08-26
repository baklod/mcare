@props(['url'])
<tr>
<td class="header" style="padding: 32px 0 20px; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 0 auto;">
        <tr>
            <td align="center" style="vertical-align: middle;">
                <img src="{{ rtrim(config('app.url'), '/') . '/assets/official-logo.png' }}" alt="MCARE Logo" width="56" height="56" style="width: 56px; height: 56px; max-width: 56px; border-radius: 14px; display: block; margin: 0 auto 10px; border: 0; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.15);" />
                <span style="display: block; font-size: 19px; font-weight: 800; color: #581c87; letter-spacing: -0.02em; line-height: 1.2;">MISSION CARE</span>
                <span style="display: block; font-size: 11px; font-weight: 700; color: #9333ea; letter-spacing: 0.14em; text-transform: uppercase; margin-top: 3px;">Training Center · Caregiving NC II</span>
            </td>
        </tr>
    </table>
</a>
</td>
</tr>
