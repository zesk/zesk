(function (w) {
	"use strict";
	const $ = w.jQuery;
	if (!$) {
		w.alert("no jQuery");
		return;
	}
	function findMostRecentRow() {
		var units = ['second', 'minute', 'hour', 'day'];
		var minUnitIndex = 99999, minValue = 99999, minTarget = null;

		$('a[href*=\\/commits\\/] span span:contains(ago):visible').each(function () {
			var i, target = $(this),
				text = target.text().toLowerCase(),
				digits = parseInt(text.replaceAll(/[^0-9]/g, ''), 10);
			for (i = 0; i <= Math.min(minUnitIndex, units.length - 1); i++) {
				if (~text.indexOf(units[i])) {
					if (i < minUnitIndex) {
						minUnitIndex = i;
						minValue = digits;
						minTarget = target;
					} else if (i === minUnitIndex) {
						if (digits < minValue) {
							minUnitIndex = i;
							minValue = digits;
							minTarget = target;
						}
					}
				}
			}
		});
		return minTarget ? minTarget.parents('tr') : null
	}
	w.findMostRecentRow = findMostRecentRow;

	var $branchRow = findMostRecentRow();
	if (!$branchRow) {
		w.alert("No branches found");
		return;
	}

	function findMostRecentActionsMenu() {
		var $branchRow = findMostRecentRow();
		if (!$branchRow) {
			return null;
		}
		var $target = $branchRow.find('button[aria-label=Branch\\ actions]').parents('td').find('[class*=Droplist__Trigger]');
		if ($target.length) {
			return $target;
		}
		return null;
	}
	w.findMostRecentActionsMenu = findMostRecentActionsMenu;

	var $link = findMostRecentActionsMenu();
	if (!$link) {
		w.alert("No branch actions found");
		return;
	}

	$link.trigger('click');
	console.log("Clicked", $link);

	var timeoutSeconds = 10,
		watcherInterval = null,
		timerKiller = null,
		watchForDialog = function () {
			// this is an iFrame which is out of security context
			var $runLink = $('section[role=dialog] button:contains(Run)');
			if ($runLink.length) {
				console.log("Found run button", $runLink);
				$runLink.trigger("click");
				clearInterval(watcherInterval);
				clearTimeout(timerKiller);
			}
		},
		watchForPopupMenu = function () {
			var $runLink = $('span[class*=ItemParts]:contains(Run pipeline):first', $branchRow);
			if ($runLink.length) {
				console.log("Found run menu item", $runLink);
				$runLink.trigger("click");
				clearInterval(watcherInterval);
				watcherInterval = setInterval(watchForDialog, 100);
			}
		};

	console.log("Watching for popup menu");
	setInterval(watchForPopupMenu, 100);
	setTimeout(function () {
		console.log("Popup did not appear in " + timeoutSeconds + " seconds - something is wrong");
		clearInterval(watcherInterval);
	}, timeoutSeconds * 1000);
})(window);
