class link {
  static get toolbox() {
    return {
      title: 'Вставить',
      icon: '<svg version="1.0" xmlns="http://www.w3.org/2000/svg"  width="512.000000pt" height="512.000000pt" viewBox="0 0 512.000000 512.000000"  preserveAspectRatio="xMidYMid meet"> <g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" fill="#000000" stroke="none"> <path d="M3882 5106 c-158 -31 -296 -84 -416 -163 -50 -33 -195 -170 -499 -472 -396 -395 -429 -430 -443 -476 -30 -102 13 -202 107 -249 31 -15 69 -26 93 -26 87 1 110 20 541 449 401 400 412 409 495 450 123 59 193 74 320 68 67 -3 129 -12 170 -26 190 -61 352 -224 413 -416 18 -57 22 -93 22 -195 0 -139 -10 -182 -73 -305 -32 -64 -94 -129 -771 -807 l-736 -736 -95 -45 c-112 -54 -170 -67 -288 -67 -152 0 -289 51 -413 153 -36 30 -76 60 -89 67 -102 55 -232 12 -285 -93 -27 -55 -31 -99 -14 -162 21 -79 213 -233 378 -305 347 -151 760 -104 1060 120 99 73 1529 1512 1577 1585 88 137 141 272 169 432 65 375 -97 780 -408 1014 -106 80 -256 152 -382 185 -125 32 -322 41 -433 20z"/> <path d="M2102 3326 c-177 -34 -333 -101 -461 -197 -107 -80 -1406 -1386 -1457 -1464 -153 -237 -212 -499 -169 -761 20 -127 49 -214 107 -326 185 -355 551 -578 949 -578 212 0 457 82 628 210 36 27 224 208 417 402 348 349 352 354 363 407 13 63 2 117 -36 173 -31 46 -111 88 -167 88 -85 -1 -113 -23 -481 -389 -373 -371 -392 -385 -545 -437 -83 -28 -281 -27 -375 3 -192 61 -355 223 -416 413 -14 41 -23 103 -26 170 -6 127 8 196 68 320 l41 85 677 676 676 677 85 41 c110 53 184 71 291 71 159 0 294 -49 420 -154 85 -71 123 -89 184 -89 89 0 159 46 196 128 22 46 24 92 7 146 -36 122 -307 304 -543 365 -124 32 -321 41 -433 20z"/></g></svg>'
    };
  }

  render() {
    let element = document.createElement('input');
    element.classList.add('link');
    element.setAttribute('placeholder', 'Ссылка на видео Youtube, пост в Twitter')
    return element;
  }

  save(blockContent) {
    return {
      url: blockContent.value
    }
  }
}