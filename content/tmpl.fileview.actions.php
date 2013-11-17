<input name="action[save]" type="submit" value="${new_file ? 'Create File' : 'Save Changes'}" accesskey="s"/>
{%if !new_file%}
<button name="action[delete]" type="submit" class="button confirm delete" title="Delete this file" accesskey="d" data-message="Are you sure you want to delete this file?">Delete</button>
{%/if%}