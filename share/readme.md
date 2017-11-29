# Zesk /share/ directory

The share directory has been slowly deprecated in Zesk, and has been trimmed to the bare minimum of core required files.

Shared items across modules and applications can be served using `zesk\Controller_Share` for development systems, and ultimately a means of generating proper alias configurations for high-performance sites.

Currently:

- `/share/js/` contains zesk.js and other tools. **This has been migrated to NPM module zeskjs**
- `/share/css/` contains some debugging stylesheets
- `/share/less/` compiles into `../css`

The `/share/` folder is shared via the `zesk\Controller_Share` as the '/share/zesk/' URI.

KMD. Wed Nov 29 17:48:05 EST 2017

## Sampling of existing share files

The following list was generated from the existing Zesk code files and contains referenced images. Updated 2017-11-29.

Any currently missing files will be deprecated in the near future.

	/share/zesk/css/exception.css
	/share/zesk/css/webfonts.css
	/share/zesk/js/control/filter/selector.js (Renamed to /share/zesk/js/control-filter-selector.js)
	/share/zesk/js/duration.js
	/share/zesk/js/exception.js
	/share/zesk/js/locale.js
	/share/zesk/js/swfobject.js
	/share/zesk/js/zesk-confirm.js
	/share/zesk/js/zesk-date.js
	/share/zesk/js/zesk.js

Removed 2017-11-29:

	/share/zesk/images/actions/delete.gif
	/share/zesk/images/actions/edit.gif
	/share/zesk/images/missing.gif
	/share/zesk/images/order/move-bottom.gif
	/share/zesk/images/order/move-down.gif
	/share/zesk/images/order/move-top.gif
	/share/zesk/images/order/move-up.gif
	/share/zesk/images/pager/$image.gif
	/share/zesk/images/sort/$sort_order.gif
	/share/zesk/images/spinner/spinner-32x32.gif
	/share/zesk/images/toggle/small-"
	/share/zesk/images/toggle/small-down.gif
	/share/zesk/images/toggle/small-right.gif
	/share/zesk/images/toggle/small-{state}.gif
	/share/zesk/jquery/farbtastic/farbtastic.css
	/share/zesk/jquery/farbtastic/farbtastic.js
	/share/zesk/jquery/flot/excanvas.pack.js
	/share/zesk/jquery/flot/jquery.flot.js
	/share/zesk/jquery/jquery.autoresize.js
	/share/zesk/jquery/jquery.corners.min.js
	/share/zesk/jquery/jquery.glow.js
	/share/zesk/jquery/jquery.hoverIntent.js
	/share/zesk/jquery/jquery.overlabel.js
	/share/zesk/jquery/themes/smoothness/ui.core.css
	/share/zesk/jquery/themes/smoothness/ui.datepicker.css
	/share/zesk/jquery/themes/smoothness/ui.theme.css
	/share/zesk/jquery/ui/ui.core.js
	/share/zesk/jquery/ui/ui.datepicker.js
	/share/zesk/jquery/ui/ui.draggable.js
	/share/zesk/jquery/ui/ui.droppable.js
	/share/zesk/jquery/ui/ui.slider.js
	/share/zesk/jquery/zesk.flot.js
	/share/zesk/jquery/zesk.ui.datepicker.js
	/share/zesk/widgets/annotate/annotate-ie.css
	/share/zesk/widgets/annotate/annotate.css
	/share/zesk/widgets/annotate/arrow-$orientation.png
	/share/zesk/widgets/annotate/arrow-B.png
	/share/zesk/widgets/annotate/arrow-L.png
	/share/zesk/widgets/annotate/arrow-R.png
	/share/zesk/widgets/annotate/arrow-T.png
	/share/zesk/widgets/box/metal/background.jpg
	/share/zesk/widgets/box/metal/bottom-left.jpg
	/share/zesk/widgets/box/metal/bottom-right.jpg
	/share/zesk/widgets/box/metal/bottom.jpg
	/share/zesk/widgets/box/metal/left.jpg
	/share/zesk/widgets/box/metal/right.jpg
	/share/zesk/widgets/box/metal/top-left.jpg
	/share/zesk/widgets/box/metal/top-right.jpg
	/share/zesk/widgets/box/metal/top.jpg
	/share/zesk/widgets/box/round/grey-corners.gif
	/share/zesk/widgets/box/round/grey-sides.gif
	/share/zesk/widgets/date/date-undo.gif
	/share/zesk/widgets/date/date-zero.gif
	/share/zesk/widgets/daterange/next-disabled.png
	/share/zesk/widgets/daterange/next.png
	/share/zesk/widgets/daterange/prev-disabled.png
	/share/zesk/widgets/daterange/prev.png
	/share/zesk/widgets/daterange/zesk.ui.datepicker.css
	/share/zesk/widgets/daterange/zesk.ui.datepicker.js
	/share/zesk/widgets/daterange/zesk.ui.datepicker3.css
	/share/zesk/widgets/daterange/zesk.ui.datepicker3.js
	/share/zesk/widgets/hoverbubble/hoverbubble-nib-TL.png
	/share/zesk/widgets/hoverbubble/hoverbubble.js
	/share/zesk/widgets/iplist/iplist.css
	/share/zesk/widgets/iplist/iplist.js
	/share/zesk/widgets/layout/layout.css
	/share/zesk/widgets/layout/layout.js
	/share/zesk/widgets/pager/pager.css
	/share/zesk/widgets/slider/slider.css
	/share/zesk/widgets/tab/diagonal/tab0.gif
	/share/zesk/widgets/tab/diagonal/tab1.gif
	/share/zesk/widgets/thickbox/loadingAnimation.gif
	/share/zesk/widgets/video/flvplayer.swf
