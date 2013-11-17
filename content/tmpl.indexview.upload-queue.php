{%if uploads%}
	{%each uploads%}
	<tr data-filename="${file.name}">
		<td>${file.name}<input type="checkbox" name="items[${name}]"/></td>
		<td>${file.size}</td>
		<td>0</td>
	</tr>
	{%/each%}
	{%if uploads.length == 0%}
	<tr class="odd">
		<td class="inactive" colspan="5">Empty.</td>
	</tr>
	{%/if%}
{%/if%}