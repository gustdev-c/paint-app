canvas=document.getElementById('canvas');
contexto=canvas.getContext("2d");
tamanoPincel = document.getElementById("tamanoPincel");
color = document.getElementById("color");


contexto.strokeStyle="rgba(128,128,128,1.0)";
contexto.fillStyle="rgba(128,128,128, 1.0)";
color.addEventListener("change", (e) => {
	contexto.strokeStyle= e.target.value;
	contexto.fillStyle= e.target.value;
});

contexto.lineWidth=1;
tamanoPincel.addEventListener("change", (e) => {
	contexto.lineWidth = e.target.value;
});

canvas.addEventListener('click',function(e){ //ante un simple click, creo un punto
	if (getRadioValue("tool") === "pincel") {
		contexto.globalCompositeOperation = 'source-over';
	} else if(getRadioValue("tool") === "borrador") {
		contexto.globalCompositeOperation = 'destination-out';
	}

	contexto.beginPath();
	contexto.arc(e.offsetX, e.offsetY, contexto.lineWidth, 0, lineWidth);
	contexto.fill();
}); //contemplo tambiÃ©n trazos continuos sin soltar el botÃ³n

canvas.addEventListener('mousemove',function(e){ //al mover el puntero sobre el lienzo
if(e.buttons) contexto.fillRect(e.offsetX,e.offsetY, 0, 0); //si el botÃ³n estÃ¡ presionado
});

ahora=false; //flag que indica si el botÃ³n izquierdo estÃ¡ presionado en el evento actual

canvas.addEventListener('mousedown',function(e){ahora=true;}); //botÃ³n presionado
canvas.addEventListener('mouseup',function(e){ahora=false;}); //soltaron el botÃ³n

antes=false; //flag que indica si estaba presionado en el evento anterior

function inicioTrazo(e){ if(ahora){//si estÃ¡ presionado, comienza a dibujar una linea continua
contexto.lineJoin = contexto.lineCap = 'round';
contexto.beginPath(); //inicio el trazado
contexto.moveTo(e.offsetX,e.offsetY); //en la posiciÃ³n de inicio
}
antes=ahora; //el flag del evento anterior, toma el valor del actual
}
canvas.addEventListener('mousemove',function(e){ //al mover el puntero sobre el lienzo
if(!antes) inicioTrazo(e); //si el botÃ³n no estaba presionado, veo si ahora lo estÃ¡ y dibujo
else if(ahora){ //si estuvo presionado y sigue presionado
contexto.lineTo(e.offsetX,e.offsetY); //voy agregando puntos al trazo
contexto.stroke(); //y dibujando
}else{ //si estuvo presionado y acaban de soltarlo
antes=false; //el flag del evento anterior, toma el valor de botÃ³n suelto del actual
}
}); //contemplo finalmente, si saliÃ³ del lienzo con el botÃ³n presionado y volviÃ³ sin soltarlo
canvas.addEventListener('mouseenter',function(e){ //el curso entrÃ³ al lienzo
if(!e.buttons) ahora=false; else inicioTrazo(e); //si el botÃ³n no estÃ¡ presionado, no dibujo
});

function getRadioValue(herramientas)
{
    var elements = document.getElementsByName(herramientas);
    for (var i = 0, l = elements.length; i < l; i++)
    {
        if (elements[i].checked)
        {
            return elements[i].value;
        }
    }
}

