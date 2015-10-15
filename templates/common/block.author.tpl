{if isset($author)}
	<div class="media ia-item ia-item--author-block">
		<a class="pull-left" href="{ia_url type='url' item='members' data=$author}">
			{if $author.avatar}
				{$avatar = $author.avatar|unserialize}
				{if $avatar}
					{printImage imgfile=$avatar.path width=80 class='img-circle' title=$author.fullname|default:$author.username}
				{else}
					<img src="{$img}no-avatar.png" class="img-circle" width="80" alt="{$author.username}">
				{/if}
			{else}
				<img src="{$img}no-avatar.png" class="img-circle" width="80" alt="{$author.username}">
			{/if}
		</a>
		<div class="media-body">
			<h4 class="media-heading"><a href="{ia_url type='url' item='members' data=$author}">{$author.fullname}</a></h4>
			{if !empty($author.member_info)}
				<p class="ia-item-body">{$author.member_info}</p>
			{/if}
			<ul class="ia-list-items">
				<li><i class="icon-file-alt"></i> {lang key='articles'}: {$author.articles_num|string_format:'%d'} <a href="{$author.rss}" title="{lang key='rss'}"><i class="rss"></i></a></li>
				<li><i class="icon-envelope-alt"></i> <a href="#send-email-box" data-toggle="modal">{lang key='send_email'}</a></li>
			</ul>
		</div>
	</div>

	<div id="send-email-box" class="modal hide fade">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3>{lang key="send_email"}</h3>
		</div>
		<div class="modal-body">
			<div id="author-block-alert" class="alert" style="display: none;"></div>
			<div class="row-fluid">
				<div class="span6">
					<label class="control-label" for="from-name">{lang key='your_name'}:</label>
					<div class="controls">
						<input type="text" id="from-name" name="from_name" class="input-block-level">
					</div>
				</div>
				<div class="span6">
					<label class="control-label fright" for="from-email">{lang key='your_email'}:</label>
					<div class="controls fright">
						<input type="text" id="from-email" name="from_email" class="input-block-level">
					</div>
				</div>
			</div>

			<label class="control-label" for="email-body">{lang key='msg'}:</label>
			<div class="controls">
				<textarea id="email-body" name="email_body" class="input-block-level" rows="4"></textarea>
			</div>

			{if !$member}
				<div class="captcha">
					{captcha}
				</div>
			{/if}

			<input type="hidden" id="author-id" name="author_id" value="{$author.id}">
			<input type="hidden" id="regarding-page" name="regarding" value="{$page.title|escape:'html'}">
		</div>
		<div class="modal-footer">
			<a href="{$smarty.const.IA_SELF}#" class="btn" data-dismiss="modal">{lang key='cancel'}</a>
			<a href="{$smarty.const.IA_SELF}#" class="btn btn-primary" id="send-email">{lang key='send'}</a>
		</div>
	</div>

	{ia_add_js}
$(function()
{
	$('#send-email').click(function(e)
	{
		e.preventDefault();

		if (!$(this).hasClass('disabled'))
		{
			var url = intelli.config.ia_url + 'actions/read.json';
			var params = new Object();
			$.each($('input', '#send-email-box'), function()
			{
				var input_name = $(this).attr('name');
				params[input_name] = $(this).val();
			});

			params['action'] = 'send_email';
			params['email_body'] = $('#email-body').val();

			$.ajaxSetup( { async: false } );
			$.post(url, params, function(data)
			{
				if (data.error)
				{
					$('#author-block-alert').addClass('alert-danger').removeClass('alert-success');
				}
				else
				{
					$('#author-block-alert').addClass('alert-success').removeClass('alert-danger');
					$('#send-email').addClass('disabled');
					setTimeout(function()
					{
						$('#send-email-box').modal('hide');
					}, 1500);
				}

				$('#author-block-alert').html(data.message.join('<br>')).show();
			});

			$.ajaxSetup( { async: true } );
		}
	});
});
	{/ia_add_js}
{/if}