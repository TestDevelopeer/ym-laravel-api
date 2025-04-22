class i{constructor(e){this.yaToken=e,this.currentTrack=null,this.initializePusher()}initializePusher(){this.channel=window.Echo.channel("yandex-music"),this.channel.listen(".track-updated",e=>{this.handleTrackUpdate(e)})}async startTracking(){try{const e=await fetch("/api/yandex-music/start-tracking",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content,Accept:"application/json"},body:JSON.stringify({interval:2})});if(!e.ok)throw new Error("Failed to start tracking");return await e.json()}catch(e){throw console.error("Tracking error:",e),e}}handleTrackUpdate(e){!this.currentTrack||this.currentTrack.track.id!==e.track.id?(console.log("Track changed:",e.track.title),this.currentTrack=e,this.updateUI(e)):this.currentTrack.progress_ms!==e.progress_ms&&(this.currentTrack=e,this.updateProgress(e))}updateUI(e){const t=document.getElementById("yandex-music-player");t&&(t.innerHTML=`
<div class="flex items-center space-x-4 mb-6">
            <img src="${e.track.image_url}" alt="Обложка" class="w-24 h-24 rounded-lg">
            <div>
                <h2 class="text-xl font-semibold">${e.track.title}</h2>
                <p class="text-gray-600">${e.track.artists[0].name}</p>
                <p class="text-sm text-gray-500">Альбом: ${e.track.albums[0].title}</p>
            </div>
        </div>
        <div class="flex justify-between items-center text-sm text-gray-500">
            <span id="progress">${this.formatTime(e.progress_ms)}</span>
            <span id="duration">${this.formatTime(e.duration_ms)}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
            <div class="bg-yellow-100 h-2.5 rounded-full" id="percentage"
                 style="width: ${e.progress_ms/e.duration_ms*100}%"></div>
        </div>
                <div class="mt-6 flex justify-center space-x-4">
            <a href="https://music.yandex.ru/track/${e.track.id}" target="_blank"
               class="px-4 py-2 bg-yellow-100 text-white rounded hover:bg-blue-700 transition">
                Слушать в Яндекс Музыке
            </a>
        </div>
            `)}updateProgress(e){const t=document.querySelector("#progress");t&&(t.textContent=this.formatTime(e.progress_ms));const r=document.querySelector("#percentage");r&&(r.style.width=`${e.progress_ms/e.duration_ms*100}%`)}formatTime(e){const t=Math.floor(e/1e3),r=Math.floor(t/60),n=t%60;return`${r}:${n<10?"0":""}${n}`}stopTracking(){this.channel&&(this.channel.unbind(),this.pusher.unsubscribe("yandex-music"))}}const c=void 0,a=new i(c);a.startTracking().catch(s=>{console.error("Failed to start tracking:",s)});
