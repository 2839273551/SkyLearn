            function watermark(t1,t2,t3){
                var maxWidth = document.documentElement.clientWidth;
                var maxHeight = document.documentElement.clientHeight;
                var intervalWidth = 320;    //é—´éš”å®½åº¦
                var intervalheight = 180;   //é—´éš”é«˜åº¦
                var rowNumber = (maxWidth - 40 -200) / intervalWidth;    //æ¨ªå‘ä¸ªæ•°
                var coumnNumber = (maxHeight - 40-80) / intervalheight;   //çºµå‘ä¸ªæ•°

                //é»˜è®¤è®¾ç½®
                var defaultSettings = {
                    watermark_color: '#aaa', //æ°´å°å­—ä½“é¢œè‰²
                    watermark_alpha: 0.40, //æ°´å°é€æ˜Žåº¦
                    watermark_fontsize: '15px', //æ°´å°å­—ä½“å¤§å°
                    watermark_font: 'å¾®è½¯é›…é»‘', //æ°´å°å­—ä½“
                    watermark_width: 200, //æ°´å°å®½åº¦
                    watermark_height: 80, //æ°´å°é•¿åº¦
                    watermark_angle: 15 //æ°´å°å€¾æ–œåº¦æ•°
                };

                var _temp = document.createDocumentFragment();
                for(var i = 0; i < rowNumber; i++){
                    for(var j = 0; j < coumnNumber; j++){
                        var x = intervalWidth*i + 20;
                        var y = intervalheight*j + 30;
                        var mark_div = document.createElement('div');
                        mark_div.id = 'mark_div' + i + j;
                        mark_div.className = 'mark_div';
                        ///ä¸‰ä¸ªèŠ‚ç‚¹
                        var span0 = document.createElement('div');
                        span0.appendChild(document.createTextNode(t1));
                        var span1 = document.createElement('div');
                        span1.appendChild(document.createTextNode(t2));
                        var span2 = document.createElement('div');
                        span2.appendChild(document.createTextNode(t3));
                        mark_div.appendChild(span0);
                        mark_div.appendChild(span1);
                        mark_div.appendChild(span2);
                        //è®¾ç½®æ°´å°divå€¾æ–œæ˜¾ç¤º
                        mark_div.style.webkitTransform = "rotate(-" + defaultSettings.watermark_angle + "deg)";
                        mark_div.style.MozTransform = "rotate(-" + defaultSettings.watermark_angle + "deg)";
                        mark_div.style.msTransform = "rotate(-" + defaultSettings.watermark_angle + "deg)";
                        mark_div.style.OTransform = "rotate(-" + defaultSettings.watermark_angle + "deg)";
                        mark_div.style.transform = "rotate(-" + defaultSettings.watermark_angle + "deg)";
                        mark_div.style.visibility = "";
                        mark_div.style.position = "absolute";
                        mark_div.style.left = x + 'px';
                        mark_div.style.top = y + 'px';
                        mark_div.style.overflow = "hidden";
                        mark_div.style.zIndex = "9999";
                        mark_div.style.pointerEvents = 'none'; //pointer-events:none  è®©æ°´å°ä¸é˜»æ­¢äº¤äº’äº‹ä»¶
                        mark_div.style.opacity = defaultSettings.watermark_alpha;
                        mark_div.style.fontSize = defaultSettings.watermark_fontsize;
                        mark_div.style.fontFamily = defaultSettings.watermark_font;
                        mark_div.style.color = defaultSettings.watermark_color;
                        mark_div.style.textAlign = "center";
                        mark_div.style.width = defaultSettings.watermark_width + 'px';
                        mark_div.style.height = defaultSettings.watermark_height + 'px';
                        mark_div.style.display = "block";

                        _temp.appendChild(mark_div);
                    }
                }
                document.body.appendChild(_temp);
            }

          