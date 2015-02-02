		loadidpPlayer();
		
		function loadidpPlayer(){
			jQuery(document).ready(function(){
				var player = '<div class="idp_player-album_art"><img src="" /></div><div class="idp_player-display"><div class="idp_player-information_display">'+
				'<p><span class="idp_player-information_tag">Track: </span><span class="idp_player-track idp_player-information">No Track</span></p>'+
				'<p><span class="idp_player-information_tag">Album: </span><span class="idp_player-album idp_player-information">N/A</span></p>'+
				'<p><span class="idp_player-information_tag">Artist: </span><span class="idp_player-artist idp_player-information">N/A</span></p>'+
			'</div><div class="idp_player-controls">'+
				'<div class="idp_player-control_button"><span class="idp_player-button idp_player-play_button idp-icon-play" data-activity="play"></span></div>'+
				'<div style="">&nbsp;</div>'+
				'<span class="idp_player-time_display"><span class="idp_player-current_time">00:00:00</span>/<span class="idp_player-duration">00:00:00</span></span>'+
				'<div style="">&nbsp;</div>'+
				//'<div class="idp_player-control_button"><span class="idp_player-button idp_player-mute_button idp-icon-volume-up" data-activity="mute"></span></div>'+
				'<div class="idp_player-control_button"><span class="idp_player-button idp_player-skip_button idp-icon-fast-fw" data-activity="skip"></span></div>'+
			'</div><div class="idp_player-playlist_display idp_player-extras_display">Unable to load the playlist!</div>'+
			'<div class="idp_player-live_display idp_player-extras_display">'+
				'<p class="idp_player-live_text">Listen Live:</p>'+
				'<p class="idp_player-live_status">Feature is under construction. Check the homepage for live information.</p>'+
			'</div></div><div class="idp_player-extras"><div class="idp_player-extras_tab idp_player-extras_top idp_player-button" data-activity="playlist"><span class="idp_player-playlist_button idp-icon-th-list"></span></div>'+
			'<div class="idp_player-extras_tab idp_player-extras_bottom idp_player-button" data-activity="live"><span class="idp_player-live_button idp-icon-circle"></span></div>'+
		'</div>';
		jQuery('.idp_player').empty().html(player);
				var idpPlayers = [];
				var audioType = testAudio();
				var players = 0;
				jQuery('.idp_player').each(function(){
					idpPlayers[idpPlayers.length] = new idpPlayer(this,audioType,players);
					players++;
				});
			});
		}
		
		function testAudio(){
			var testAudio = new Audio();
			if(testAudio.canPlayType("audio/mpeg")=="probably"){
				return "mpeg";
			}else if(testAudio.canPlayType("audio/ogg")=="probably"){
				return "ogg";
			}else if(testAudio.canPlayType("audio/mpeg")=="maybe"){
				return "mpeg";
			}else{
				return false;
			}
		}

		function idpPlayer(playerEl,audioType,playerID) {
			var thisPlayer = jQuery(playerEl),
				playerData = thisPlayer.data(),
				mountPos = playerData.live_source.lastIndexOf("/"),
				streamBase = playerData.live_source.substring(0,mountPos),
				mount = playerData.live_source.substring(mountPos),
				audioType = testAudio(),
				source = (audioType === "mpeg") ? playerData.source : playerData.ogg_source,
				duration = (typeof playerData.duration !== undefined) ? playerData.duration.toString().toHHMMSS() : "",
				defaultTrack = {
					source:source,
					track:playerData.track,
					album:playerData.album,
					artist:playerData.artist,
					albumArt:playerData.album_art,
					duration:duration,
					audio:new Audio(source)
				},
				liveTrack = {
					source:playerData.live_source,
					mount:mount,
					json:streamBase+"/json.xsl",
					albumArt:playerData.album_art_live,
					audio:new Audio()
				},
				playlist = {
					source:playerData.playlist_source,
					type:"mrss",
					tracks:[]
				},
				state = {
					status:"stop",
					streamReady:false,
					currentArt:defaultTrack.albumArt
				},
				elements = {
					player:thisPlayer,
					track:thisPlayer.find('.idp_player-track'),
					album:thisPlayer.find('.idp_player-album'),
					artist:thisPlayer.find('.idp_player-artist'),
					albumArt:thisPlayer.find('.idp_player-album_art img'),
					live:thisPlayer.find('.idp_player-live_display'),
					playlist:thisPlayer.find('.idp_player-playlist_display'),
					buttons:thisPlayer.find('.idp_player-button'),
					currentTime:thisPlayer.find('.idp_player-current_time'),
					duration:thisPlayer.find('.idp_player-duration')
				};
			var startInLiveMode = (playerData.live=="true") ? true : false;
			if(playlist.type==="mrss") playlist.tracks = parseRSSFeed(playlist.source);
			updateInformationDisplay();
			checkIfLive(liveTrack.json,liveTrack.mount);
			window.setInterval(function(){checkIfLive(liveTrack.json,liveTrack.mount);}, 10000);
			
			/*CLICK EVENTS------------------------------------*/
			elements.buttons.click(function(){
				var button = jQuery(this);
				var activity = button.data('activity');
				if(activity==="play"){
					if(state.status!=="play"){
						play();
					}else{
						pause();
					}
				}else if(activity==="mute"){
					toggleMute(button);
				}else if(activity==="skip"){
					skipAhead(30);
				}else if(activity==="playlist"){
					var opening = button.hasClass('pressed') ? false : true;
					elements.buttons.removeClass('pressed');
					if(elements.live.css('display') != 'none'){
						state.status = "stop";
						elements.live.slideToggle();
					}
					elements.playlist.slideToggle();
					if(opening) button.addClass('pressed');
				}else if(activity==="live"){
					var opening = state.status!=="live" ? true : false;
					if(opening){
						pause();
						state.status = "live";
						playLive();
					}else{
						state.status = "stop";
						stopLive();
					}
					elements.buttons.removeClass('pressed');
					if(elements.playlist.css('display') != 'none'){
						elements.playlist.slideToggle();
					}
					elements.live.slideToggle();
					if(opening) button.addClass('pressed');
				}
			});
			function startAltTrackListener(){
				jObjAltTrack = jQuery('.idp_player-feed_item');
				jObjAltTrack.click(function(){
					var thisTrack = jQuery(this);
					var thisTrackID = thisTrack.data('item');
					var thisTrackPlayer = thisTrack.closest('.idp_player');
					var thisTrackSource = playlist.tracks[thisTrackID].source;
					var thisTrackTrack = playlist.tracks[thisTrackID].track;
					var thisTrackAlbum = playlist.tracks[thisTrackID].album;
					var thisTrackArtist = playlist.tracks[thisTrackID].artist;
					var thisTrackDuration = playlist.tracks[thisTrackID].duration;
					var thisTrackArt = playlist.tracks[thisTrackID].albumArt;
					defaultTrack.source = thisTrackSource;
					defaultTrack.audio.src = thisTrackSource;
					defaultTrack.track = thisTrackTrack;
					defaultTrack.artist = thisTrackArtist;
					defaultTrack.albumArt = thisTrackArt;
					defaultTrack.duration = thisTrackDuration;
					defaultTrack.audio.load();
					updateInformationDisplay();
					elements.buttons.removeClass('pressed');
					elements.playlist.slideToggle();
					play();
				});
			}
			function skipAhead(seconds){
				defaultTrack.audio.currentTime = defaultTrack.audio.currentTime + seconds;
			}
			function toggleMute(button){
				var mutestatus = defaultTrack.audio.muted;
				mutestatus = !mutestatus;
				defaultTrack.audio.muted = mutestatus;
				if(mutestatus){
					button.addClass('idp-icon-volume-off').removeClass('idp-icon-volume-up');
				}else{
					button.addClass('idp-icon-volume-up').removeClass('idp-icon-volume-off');
				}
			}
			function play(){
				elements.player.find('.idp_player-play_button').addClass('idp-icon-pause').removeClass('idp-icon-play');
				defaultTrack.audio.play();
				state.status = "play";
			}
			function pause(){
				elements.player.find('.idp_player-play_button').addClass('idp-icon-play').removeClass('idp-icon-pause');
				defaultTrack.audio.pause();
				state.status = "pause";
			}
			
			/*INFORMATION EVENTS------------------------------------*/
			function updateInformationDisplay(){
				elements.track.empty().text(defaultTrack.track);
				elements.album.empty().text(defaultTrack.album);
				elements.artist.empty().text(defaultTrack.artist);
				elements.albumArt.attr('src', defaultTrack.albumArt);
				elements.duration.empty().text(defaultTrack.duration);
			}
			function parseRSSFeed(url){
				var createPlaylist = [];
				jQuery.ajax({
					url:url,
					dataType: 'jsonp',
					success: function(data) {
						var num = 0;
						var playlistinfo = "";
						playlistinfo += "<ul><li class=\"idp_player-playlist_text\">Other tracks:</li>";
						jQuery.each(data.feed, function(key, value){
							createPlaylist.push({
								track:value.title,
								album:data.title,
								artist:data.author.name,
								duration:value.duration,
								albumArt:value.albumart,
								source:value.mp3.url,
								//oggSource:value.ogg.url
							});
							playlistinfo += "<li class=\"idp_player-feed_item\" data-item=\""+num+"\"><span class=\"idp-icon-play\"></span>&nbsp;"+playlist.tracks[num].track+"</li>";
							num++;
						});
						playlistinfo += "</ul>";
						elements.playlist.empty().html(playlistinfo);
						startAltTrackListener();
					}
				});
				return createPlaylist;
			}
			
			defaultTrack.audio.addEventListener('timeupdate',function(){
				var currentTime = defaultTrack.audio.currentTime;
				elements.currentTime.text(currentTime.toString().toHHMMSS());
			},false);
			
			defaultTrack.audio.addEventListener('loadedmetadata', function() {
				defaultTrack.duration = (defaultTrack.audio.duration != 0 && defaultTrack.audio.duration != "NaN") ? defaultTrack.audio.duration.toString().toHHMMSS() : defaultTrack.duration;
				updateInformationDisplay();
			});
			
			/*LIVE EVENTS------------------------------------*/
			function checkIfLive(url,streammount){
				jQuery.ajax({
					url:      url,
					dataType: 'jsonp',
					jsonpCallback: 'parseMusic',
					type:     'GET',
					success:  function(data){
						var stream = (typeof data[streammount] == "object") ? true : false;
						if(stream!==state.streamReady){
							if(stream){
								streamIsLive();
							}else{
								streamIsOffAir();
							}
						}
					},
					timeout:1000
				});
			}
			
			function streamIsLive(){
				elements.buttons.filter(".idp_player-extras_bottom").css("color","red");
				liveTrack.audio.src = liveTrack.source;
				liveTrack.audio.load();
				state.streamReady = true;
				if(state.status==="live"){
					playLive();
				}else{
					changeLiveText();
				}
			}
			
			function streamIsOffAir(){
				elements.buttons.filter(".idp_player-extras_bottom").css("color","white");
				state.streamReady = false;
				changeLiveText();
			}
			
			function playLive(){
				liveTrack.audio.play();
				changeLiveText();
			}
			
			function stopLive(){
				liveTrack.audio.pause();
				changeLiveText();
			}
			
			function changeLiveText(){
				if(state.streamReady){
					jQuery(".idp_player-live_status").html("We're live now!");
					updateAlbumArt();
				}else{
					jQuery(".idp_player-live_status").html("This module is under construction, sorry!");
					updateAlbumArt();
				}
			}
			function updateAlbumArt(){
				var currentArt = state.currentArt;
				elements.albumArt.attr('src', liveTrack[currentArt]);
			}
		}
String.prototype.toHHMMSS = function () {
    var sec_num = parseInt(this, 10); // don't forget the second param
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    var time    = hours+':'+minutes+':'+seconds;
    return time;
}