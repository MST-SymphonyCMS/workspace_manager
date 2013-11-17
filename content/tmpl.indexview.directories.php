{%if directories%}
	{%each directories%}
	<tr>
		<td>
			<a href="<?= $page_url_i ?>${name}/">${name}</a>
			<input type="checkbox" name="items[${name}]"/>
		</td>
	</tr>
	{%/each%}
	{%if directories.length == 0%}
	<tr class="odd">
		<td class="inactive" colspan="5">None found.</td>
	</tr>
	{%/if%}
{%/if%}