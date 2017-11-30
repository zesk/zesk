var zeskSound = {
	inited: false,
	basedir: null,
	soundFiles: null,
	sounds: null,
	soundTransform: null,
	audioVolume: 0.5,
	soundQueue: new Array(),
	flushTimeout: null,
	allLoaded: false,

	init: function(basedir) {
		if (this.inited) return;
		this.basedir 					= basedir;
		this.soundFiles					= {
			complete: 'complete.mp3',
			bu_dup: 'bu_dup.mp3',
			sound_1: 'sound_1.mp3',
			sound_2: 'sound_2.mp3',
			sound_3: 'sound_3.mp3',
			sound_4: 'sound_4.mp3',
			sound_5: 'sound_5.mp3',
			sound_6: 'sound_6.mp3'
		};
		this.sounds 					= new Object();
		this.addFlashObjectToPage();
		this.inited						= true;
	},

	setVolume: function(volume) {
		volume = parseFloat(volume);
		if (!isNaN(volume)) {
			if (volume < 0) {
				volume = 0.0;
			} else if (volume > 1) {
				volume = 1.0;
			}
			this.audioVolume = volume;
			try {
				if (!this.soundTransform) {
					this.soundTransform = FABridge.zeskSound.create('flash.media.SoundTransform');
				}
				this.soundTransform.setVolume(volume);
			} catch(e) {
				alert(e);
			}
		}
	},

	addFlashObjectToPage: function() {
		var b = document.getElementsByTagName('body');
		var proto = window.location.protocol;
		FABridge.addInitializationCallback('zeskSound', this.flashLoaded);
		var e = document.createElement('div');
		e.id = "flashSoundObject";
		b[0].appendChild(e);
		e.innerHTML =
		'<object id="zeskSoundFlashInterface" style="position: absolute; left: -1000px;" '
		+'classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" '
		+'codebase="' + proto + '//download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" '
		+'height="1" width="1">'
		+'<param name="flashvars" value="bridgeName=zeskSound" />'
		+'<param name="src" value="'+this.basedir+'FABridge.swf" />'
		+'<embed name="zeskSoundFlashInterface" pluginspage="' + proto + '//www.macromedia.com/go/getflashplayer" '
		+'src="'+this.basedir+'FABridge.swf" height="1" width="1" flashvars="bridgeName=zeskSound" />'
		+'</object>';
	},

	flashLoaded: function() {
		zeskSound.initFlash();
	},

	initFlash: function() {
		try {
			this.setVolume(this.audioVolume);
			this.sounds = new Object();
			var sound,urlRequest;
			for(var key in this.soundFiles) {
				sound = FABridge.zeskSound.create('flash.media.Sound');
				sound.addEventListener('ioError', this.soundIOErrorHandler);
				sound.addEventListener('complete', this.soundLoaded);
				urlRequest = FABridge.zeskSound.create('flash.net.URLRequest');
				urlRequest.setUrl(this.basedir+this.soundFiles[key]);
				sound.load(urlRequest);
			}
		} catch(e) {
			alert(e);
		}
	},

	soundLoaded: function(event) {
		var sound = event.getTarget();
		for(var key in zeskSound.soundFiles) {
			var url = "" + sound.getUrl();
			var fname = zeskSound.soundFiles[key];
			if (url.substring(url.length-fname.length,url.length) == fname) {
				zeskSound.sounds[key] = sound;
			}
		}
		if (zeskSound.sounds.length == zeskSound.soundFiles.length) {
			zeskSound.allLoaded = true;
		}
	},

	soundIOErrorHandler: function(event) {
		// setTimeout is needed to avoid calling the flash interface recursively (e.g. sound on new messages):
		alert(event);
		//		setTimeout('zeskSound.doSomething(\'/error SoundIO\')', 0);
		//		setTimeout('zeskSound.doSomethingElse()', 1);
	},

	soundPlayed: function(event) {
		// soundChannel event 'soundComplete'
	},

	waitForLoad: function() {
		if (!this.flushTimeout) {
			this.flushTimeout = setTimeout('zeskSound.flushQueue()', 1000);
		}
	},
	flushQueue: function() {
		if (!this.allLoaded) {
			this.waitForLoad();
			return;
		}
		var n = 0;
		this.flushTimeout = null;
		for (n = 0; n < this.soundQueue.length; n++) {
			this.play(this.soundQueue[n]);
		}
		this.soundQueue = new Array();
	},
	play: function(soundID) {
		if (typeof soundID != 'string') return false;
		if (!this.allLoaded) {
			this.soundQueue[this.soundQueue.length] = soundID;
			this.waitForLoad();
			return;
		}
		if (this.sounds && this.sounds[soundID]) {
			try {
				// play() parameters are
				// startTime:Number (default = 0),
				// loops:int (default = 0) and
				// sndTransform:SoundTransform  (default = null)
				return this.sounds[soundID].play(0, 0, this.soundTransform);
			} catch(e) {
				alert(e);
			}
		}
		return null;
	}
};

function play(sound)
{
	zeskSound.play(sound);
}
