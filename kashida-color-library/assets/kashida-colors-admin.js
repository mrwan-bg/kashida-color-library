(function(){
    'use strict';
    var CFG=window.KashidaColorsShowcaseColorPicker||{};
    var palette=[
        '#000000','#434343','#666666','#999999','#B7B7B7','#CCCCCC','#D9D9D9','#EFEFEF','#F3F3F3','#FFFFFF',
        '#980000','#FF0000','#FF9900','#FFFF00','#00FF00','#00FFFF','#4A86E8','#0000FF','#9900FF','#FF00FF',
        '#E6B8AF','#F4CCCC','#FCE5CD','#FFF2CC','#D9EAD3','#D0E0E3','#C9DAF8','#CFE2F3','#D9D2E9','#EAD1DC',
        '#DD7E6B','#EA9999','#F9CB9C','#FFE599','#B6D7A8','#A2C4C9','#A4C2F4','#9FC5E8','#B4A7D6','#D5A6BD',
        '#CC4125','#E06666','#F6B26B','#FFD966','#93C47D','#76A5AF','#6D9EEB','#6FA8DC','#8E7CC3','#C27BA0',
        '#A61C00','#CC0000','#E69138','#F1C232','#6AA84F','#45818E','#3C78D8','#3D85C6','#674EA7','#A64D79',
        '#85200C','#990000','#B45F06','#BF9000','#38761D','#134F5C','#1155CC','#0B5394','#351C75','#741B47'
    ];

    function validHex(v){return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v||'');}
    function normalizeHex(v){
        v=(v||'').trim();
        if(/^#[0-9a-f]{3}$/i.test(v)) v='#'+v.substring(1).split('').map(function(x){return x+x;}).join('');
        return validHex(v)?v.toUpperCase():null;
    }

    function mountRow(row){
        if(row.dataset.kcsPickerBound==='1')return;
        row.dataset.kcsPickerBound='1';
        var hex=row.querySelector('.kcs-hex-text'),native=row.querySelector('.kcs-hex-input'),preview=row.querySelector('.kcs-color-preview'),panel=row.querySelector('.kcs-picker-panel'),custom=row.querySelector('.kcs-custom-color'),paletteWrap=row.querySelector('.kcs-color-palette');
        if(!hex||!native||!preview||!panel||!paletteWrap)return;

        function setColor(value,close){
            var color=normalizeHex(value); if(!color)return;
            hex.value=color;
            native.value=color;
            preview.style.backgroundColor=color;
            if(custom)custom.value=color;
            var sw=row.querySelector('.kcs-picker-value-swatch'); if(sw)sw.style.backgroundColor=color;
            if(close)panel.hidden=true;
        }
        function renderPalette(){
            paletteWrap.innerHTML='';
            palette.forEach(function(c){
                var b=document.createElement('button');
                b.type='button';
                b.className='kcs-palette-swatch';
                b.style.backgroundColor=c;
                b.title=c;
                b.setAttribute('aria-label',c);
                b.onclick=function(){setColor(c,true);};
                paletteWrap.appendChild(b);
            });
        }
        function open(){
            panel.hidden=false;
            var color=normalizeHex(native.value||hex.value)||'#FFFFFF';
            setColor(color,false);
        }
        renderPalette();
        preview.onclick=open;
        preview.onkeydown=function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();open();}};
        var close=row.querySelector('.kcs-picker-close');
        if(close)close.onclick=function(){panel.hidden=true;};
        var apply=row.querySelector('.kcs-picker-close-apply');
        if(apply)apply.onclick=function(){setColor(custom?custom.value:hex.value,true);};
        if(custom)custom.addEventListener('input',function(){setColor(this.value,false);});
        hex.addEventListener('input',function(){var c=normalizeHex(this.value);if(c)setColor(c,false);});
        hex.addEventListener('change',function(){var c=normalizeHex(this.value);if(c)setColor(c,false);else this.value=native.value;});
        var remove=row.querySelector('.kcs-remove-color');if(remove)remove.onclick=function(){row.remove();};
        var upload=row.querySelector('.kcs-upload-btn'),removeImg=row.querySelector('.kcs-remove-img'),sample=row.querySelector('.kcs-sample-preview'),url=row.querySelector('.kcs-sample-url'),id=row.querySelector('.kcs-sample-id');
        if(upload)upload.onclick=function(){var frame=wp.media({title:CFG.chooseSample||'Choose Sample Image',button:{text:CFG.select||'Select'},multiple:false,library:{type:'image'}});frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();url.value=a.url;id.value=a.id;sample.src=a.url;sample.classList.add('has-image');removeImg.classList.add('visible');});frame.open();};
        if(removeImg)removeImg.onclick=function(){url.value='';id.value='';sample.src='';sample.classList.remove('has-image');removeImg.classList.remove('visible');};
        setColor(normalizeHex(hex.value)||'#FFFFFF',false);
    }
    function init(){
        var list=document.getElementById('kcs-colors-list'),template=document.getElementById('kcs-color-template'),add=document.getElementById('kcs-add-color');if(!list||!template||!add)return;
        list.querySelectorAll('.kcs-color-row').forEach(mountRow);
        var n=list.querySelectorAll('.kcs-color-row').length;
        add.onclick=function(){var holder=document.createElement('div');holder.innerHTML=template.innerHTML.replace(/__INDEX__/g,n++);var row=holder.firstElementChild;list.appendChild(row);mountRow(row);};
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
