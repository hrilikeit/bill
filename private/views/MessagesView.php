<?php
class MessagesView
{
	private $Member;
	private $Entertainer;

	public function __construct(Member $Member)
	{
		$this->Member = $Member;
		if (StaysailIO::session('Entertainer.id')) {
			$this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
		} else {
			$this->Entertainer = $this->Member->getAccountOfType('Entertainer');
		}
	}

	public function getHTML()
	{
		$writer = new StaysailWriter();
		$writer->h1('Messages');
		//$writer->p('Click on a message to the left to view it, or click the Compose Message button below.');
		$button = "<a href=\"?mode=Message&job=compose\" class=\"button\">Write new Message</a>";
		$writer->addHTML($button);

		return $writer->getHTML();
	}

	public function getMessageListHTML()
	{
		// NOTE: We intentionally do *not* load messages server-side here.
		// Messages are loaded via AJAX (Unread / Read / Sent) with pagination.
		// This improves initial page load time.
		$writer = new StaysailWriter();

		// Tabs
		// NOTE: add whitespace between anchors so the UI is still readable even if CSS fails to load.
		$writer->addHTML(
			'<div class="button-container">'
				. '<a href="#" data-type="unread" class="active">Unread</a> '
				. '<a href="#" data-type="read">Read</a> '
				. '<a href="#" data-type="sent">Sent</a>'
			. '</div>'
		);

		// Heading (keeps the UI consistent with the old design)
		$writer->addHTML('<h1 id="messagesHeading" class="inbox_new">New Messages</h1>');

		// Inline styles as a safety net (prevents UI from looking broken if a cached CSS file is served).
		$writer->addHTML(
			'<style>'
			. '.button-container{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:10px 0;}'
			. '.button-container a{display:inline-block;padding:6px 10px;border:1px solid #d1d1d1;border-radius:6px;text-decoration:none;background-color:#f5f5f5;color:#333;font-size:10pt;}'
			. '.button-container a.active{background-color:#e8e8e8;border-color:#bdbdbd;font-weight:bold;}'
			. '#messagesHeading{margin-top:15px;}'
			. '#paginationContainer{display:flex;gap:6px;justify-content:center;align-items:center;flex-wrap:wrap;margin:12px 0 0 0;}'
			. '#paginationContainer span{padding:0 6px;color:#777;}'
			. '.pagination-page-btn{padding:5px 9px;border:1px solid #d1d1d1;border-radius:6px;background-color:#f5f5f5;cursor:pointer;}'
			. '.pagination-page-btn.pagination-active{background-color:#e8e8e8;border-color:#bdbdbd;font-weight:bold;}'
			. '.private_message_selector_unread_time{display:block;text-align:right;margin-right:10px;}'
			. '</style>'
		);

		$writer->addHTML('<div id="messagesContainer"></div>');
		$writer->addHTML('<div id="paginationContainer"></div>');

		// Load the messages pagination script only on the Messages page.
		// NOTE: Add a cache-buster so updated JS is picked up immediately after deployment.
		$writer->addHTML('<script src="/js/messagesPagination.js?v=2" defer></script>');

		return $writer->getHTML();
	}
}