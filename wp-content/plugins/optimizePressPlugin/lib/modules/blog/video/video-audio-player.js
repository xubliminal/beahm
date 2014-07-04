opjq(document).ready(function(){
	var $ = opjq;

	var initPlayer = function ($el, iteration) {
		var opts = {
				clip:{
					autoPlay: false,
					autoBuffering: false,
					scaling: 'fit'
				},
				plugins:{}
			};
		var elHtml = $el.html();
		var $flowplayer = $el.find('.flowplayer');
		var $t = $flowplayer;
		var mainHref;
		var tempExt;
		var posterImg;
		var videoHtml;
		var iteration = iteration || 1;

		//Go through all flowplayer videos (and not others)
		if ($flowplayer.length === 0 && !$el.hasClass('audio-plugin')) {
			$el.addClass('not-flowplayer');
			return;
		}

		if($t.hasClass('auto-play') || iteration > 1){
			opts.clip.autoPlay = true;
		}
		if($t.hasClass('hide-controls')){
			opts.plugins.controls = null;
		}
		if($t.hasClass('auto-buffer') || iteration > 1){
			opts.clip.autoBuffering = true;
		}
		if ($t.find('img').attr('src')) {
			opts.clip.poster = true;
			posterImg = $t.find('img').attr('src');
		}
		// File extension
		tempExt = $t.attr('href') ? $t.attr('href').substr($t.attr('href').lastIndexOf('.') + 1) : '';
		tempExt1 = $t.attr('data-href1') ? $t.attr('data-href1').substr($t.attr('data-href1').lastIndexOf('.') + 1) : '';
		tempExt2 = $t.attr('data-href2') ? $t.attr('data-href2').substr($t.attr('data-href2').lastIndexOf('.') + 1) : '';

		tempExt = tempExt.toLowerCase();
		tempExt1 = tempExt1.toLowerCase();
		tempExt2 = tempExt2.toLowerCase();

		mainHref = $t.attr('href');

		setMediaProps = {}
		setMediaProps[tempExt] = $t.attr('href');

		if ($t.hasClass('audio-player')) {
			//Audio
			tempExt = (tempExt === 'm4a') ? 'mp4' : tempExt;
			tempExt1 = (tempExt1 === 'm4a') ? 'mp4' : tempExt1;
			tempExt2 = (tempExt2 === 'm4a') ? 'mp4' : tempExt2;

			tempExt = (tempExt === 'mp3') ? 'mpeg' : tempExt;
			tempExt1 = (tempExt1 === 'mp3') ? 'mpeg' : tempExt1;
			tempExt2 = (tempExt2 === 'mp3') ? 'mpeg' : tempExt2;

			$t.removeClass('flowplayer');
			$t.append('<audio ' + (opts.clip.autoBuffering ? 'autobuffer="auto" ' : '') + (opts.plugins.controls ? ' controls' : '') + '>' +
					'<source src="' + $t.attr('href') + '" type="audio/' + tempExt + '" style="width:100%; height: 100%;">' +
					'<source src="' + $t.attr('data-href1') + '" type="audio/' + tempExt1 + '" style="width:100%; height: 100%;">' +
					'<source src="' + $t.attr('data-href2') + '" type="audio/' + tempExt2 + '" style="width:100%; height: 100%;">' +
				'</audio>');

			$t.find('audio').mediaelementplayer({
				alwaysShowControls: true,
				enableAutosize: true,
				videoWidth: -1,
				videoHeight: -1,
				audioWidth: '100%',
				audioHeight: 30,
				enablePluginDebug: false,
				plugins: ['flash'],
				pluginPath: OP.mediaelementplayer,
				flashName: 'flashmediaelement.swf'
			});

			if (opts.clip.autoPlay) {
				$t.find('audio').attr('autoplay', 'autoplay');
			}
		} else {
			//Video
			if (opts.clip.poster) {
				$t.find('img').remove();
			}

			tempExt = (tempExt === 'm4v') ? 'mp4' : tempExt;
			tempExt1 = (tempExt1 === 'm4v') ? 'mp4' : tempExt1;
			tempExt2 = (tempExt2 === 'm4v') ? 'mp4' : tempExt2;

			videoHtml = '<video ' +
							(opts.clip.autoBuffering ? '' : 'preload="none" ') +
							(opts.plugins.controls ? ' controls' : '') +
							(opts.clip.poster ? ' poster="' + posterImg + '"' : '') +'>' +
							'<source src="' + $t.attr('href') + '" type="video/' + tempExt + '">' +
							($t.attr('data-href1') ? '<source src="' + $t.attr('data-href1') + '" type="video/' + tempExt1 + '">' : '') +
							($t.attr('data-href2') ? '<source src="' + $t.attr('data-href2') + '" type="video/' + tempExt2 + '">' : '') +
						'</video>';

			$t.append(videoHtml);

			if (opts.clip.autoPlay) {
				$t.find('video').attr('autoplay', 'autoplay');
			}

			$t.flowplayer({
				adaptiveRatio: true,
				swf: OP.flowplayerHTML5,
				key: OP.flowplayerKey,
				logo: OP.flowplayerLogo
			});

			$t.bind("error", function(e, api) {
				var $message;
				var messageText;

				if (iteration > 4) {
					$message = $t.find('.fp-message h2');
					$message.text('Unable to load video');
					//$message.text(messageText.replace('html5: ', ''));
					$t.find('.fp-message p').html('<a target="_blank" download="' + mainHref + '" href="' + mainHref + '">Download</a>');
					return;
				}
				iteration += 1;

				$t.unbind();
				$t.unload();
				$t.remove();

				$el.append(elHtml);
				initPlayer($el, iteration);
			});
		}

		$t.removeAttr('href');

	}

	$('.video-plugin, .video-plugin-new, .audio-plugin').each(function(){
		initPlayer($(this));
	});

});