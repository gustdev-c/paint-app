<?php
	
	//Aumento el tiempo de ejecución para todos los procesos de prueba
	ini_set('max_execution_time', '600'); //10 minutos
	//Llamadas recursivas anidadas
	ini_set('xdebug.max_nesting_level', 1024);
	//Aumento el límite de memoria para imágenes grandes
	ini_set('memory_limit', '1024M');

	//CANALES RGBA
	define('R',0);
	define('G',1);
	define('B',2);
	define('A',3);

	//CANALES YCbCr
	define('Y',0);
	define('Cb',1);
	define('Cr',2);

	class imageArray extends imageHash{

		protected function load(){
			//Vuelvo a cargar la imagen
			$foo='imagecreatefrom'.self::extension($this->type);
			$this->img=$foo($this->filename);
			if(!$this->img){
				throw new Exception("Problemas leyendo el archivo.");
			}
			$this->RGB=new SplFixedArray($this->width*$this->height*4);

			$offset=$this->height<<2; //Multiplico por 4 canales
			//Creo un array bidimensional con los valores de cada capa RGBA
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					//Leo los bytes del pixel
					$bytes=imagecolorat($this->img,$x,$y);
					//Separo los canales
					$colors=imagecolorsforindex($this->img,$bytes);
					//Cada canal queda un array de 4 llaves: 'red', 'green', 'blue' y 'alpha'.
					$xyoffset=$xoffset+($y<<2);
					$this->RGB[$xyoffset+R]=$colors['red'];
					$this->RGB[$xyoffset+G]=$colors['green'];
					$this->RGB[$xyoffset+B]=$colors['blue'];
					$this->RGB[$xyoffset+A]=$colors['alpha'];
				}
			}
		}
		protected function initYCbCr(){
			$this->YCbCr=new SplFixedArray($this->width*$this->height*4);
		}
	}

	class imageHash{
		//Propiedades
		protected $RGB;
		protected $YCbCr;
		protected $type;
		protected $width;
		protected $height;
		protected $filename;
		protected $img;

		//ALTURA
		public function getHeight(){
			return $this->height;
		}

		//ANCHO
		public function getWidth(){
			return $this->width;
		}

		//Extension
		public static function extension($type){
			switch($type){
				case IMAGETYPE_PNG:
					return 'png';
				case IMAGETYPE_JPEG:
					return 'jpeg';
				case IMAGETYPE_BMP:
					return 'bmp';
				case IMAGETYPE_GIF:
					return 'gif';
				default:
					throw new Exception("Tipo de archivo no soportado.");

			}
		}

		//CONSTRUCTOR
		function __construct($filename=''){

			if($filename){
				//A partir del nombre empiezo a cargar las propiedades
				$this->filename=$filename;
				//Intento abrir el archivo
				if(file_exists($this->filename)){
					//Ancho, Alto y Tipo
					//[$this->width, $this->height, $this->type]=getimagesize($this->filename);	//PHP 7.2+
					list($this->width, $this->height, $this->type)=getimagesize($this->filename);
					$this->load();
					//Método alternativo para leer ancho y alto
					//$this->width=imagesx($this->img);
					//$this->height=imagesy($this->img);
				}else{
					throw new Exception("El archivo no existe.");
				}
			}else{
				//Objeto vació para copias
			}
		}

		//LEER
		protected function load(){
			//Vuelvo a cargar la imagen
			$foo='imagecreatefrom'.self::extension($this->type);
			$this->img=$foo($this->filename);
			if(!$this->img){
				throw new Exception("Problemas leyendo el archivo.");
			}
			$this->RGB=array();
			for($x=0;$x<$this->width;++$x){
				for($y=0;$y<$this->height;++$y){
					//Leo los bytes del pixel
					$bytes=imagecolorat($this->img,$x,$y);
					//Separo los canales
					$colors=imagecolorsforindex($this->img,$bytes);
					//Cada canal queda un array de 4 llaves: 'red', 'green', 'blue' y 'alpha'.
					$this->RGB[]=$colors['red'];
					$this->RGB[]=$colors['green'];
					$this->RGB[]=$colors['blue'];
					$this->RGB[]=$colors['alpha'];
				}
			}
		}
		protected function initYCbCr(){
			$this->YCbCr=array_fill(0,$this->width*$this->height*4,0);
		}

		//RECARGAR
		public function reload(){
			//Vuelvo a cargar la imagen
			$this->load();
		}

		//DESTRUCTOR
		function __destruct(){
			//Libero memoria
			if(is_resource($this->img) && get_resource_type($this->img)=='gd') imagedestroy($this->img);
			unset($this->RGB);
			if(isset($this->YCbCr)) unset($this->YCbCr);
		}

		//MOSTRAR EN HTML
		protected function imgdraw($newimg){
			ob_start();
			//Elijo función
			$foo='image'.self::extension($this->type);
			//Capturo el flujo de datos
			$foo($newimg);
			$data = ob_get_clean();
			//@ob_end_clean();
			//Creo una imagen HTML
			echo '<img src="data:image/png;base64,'.base64_encode($data).'" />'.PHP_EOL;
		}

		//SALVAR
		public function save($filename=NULL){
			//Actualizo
			$this->update();
			//Elijo función
			$foo='image'.self::extension($this->type);
			//Salvo con la ruta del argumento, o con el nombre del constructor
			$foo($this->img,isset($filename)?$filename:$this->filename);
		}

		//PERFIL
		public function profile($y){
			$height=256;
			//Creo un lienzo vació de altura fija
			$newimg=imagecreatetruecolor($this->width,$height);
			//Color de fondo
			$bgcolor=imagecolorallocate($newimg,255,255,255);
			//Pinto el fondo
			imagefilledrectangle($newimg,0,0,$this->width-1,$height-1,$bgcolor);
			//Configuro los colores de los canales
			$r=imagecolorallocate($newimg,255,0,0); //rojo
			$g=imagecolorallocate($newimg,0,255,0); //verde
			$b=imagecolorallocate($newimg,0,0,255); //azul
			//La ultima fila de pixeles del gráfico es la altura-1
			$h=$height-1;
			//Dibujo líneas entre los distintos valores de cada canal
			$yoffset=$y<<2;
			$y0=array($this->RGB[$yoffset+R],$this->RGB[$yoffset+G],$this->RGB[$yoffset+B]);
			//Recorro la fila
			$offset=$this->height<<2; //Multiplico por 4 canales
			for($x=0;$x<$this->width;++$x){
				$xyoffset=$x*$offset+$yoffset;
				//Pixel actual
				$y1=array($this->RGB[$xyoffset+R],$this->RGB[$xyoffset+G],$this->RGB[$xyoffset+B]);
				//Dibujo una línea entre pixeles vecinos por canal.
				//El valor 0 se dibuja en la altura 255 y viceversa
				imageline($newimg,$x-1,$h-$y0[R],$x,$h-$y1[R],$r);
				imageline($newimg,$x-1,$h-$y0[G],$x,$h-$y1[G],$g);
				imageline($newimg,$x-1,$h-$y0[B],$x,$h-$y1[B],$b);
				//La próxima iteración, la línea comienza en el valor actual
				$y0=array($this->RGB[$xyoffset+R],$this->RGB[$xyoffset+G],$this->RGB[$xyoffset+B]);
			}
			//Dibujo el perfil
			$this->imgdraw($newimg);
			//Libero memoria
			imagedestroy($newimg);
		}

		//AJUSTAR A BYTE
		public static function Byte($value,$max=255){
			//Devuelve un entero entre 0 y 255
			return (int) round(max(0,min($max,$value)));
		}

		//BRILLO
		public function CandB($contrast=1,$brightness=0){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Por cada canal de cada pixel del array
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Modifico relativamente el brillo
					$this->RGB[$xyoffset+R]=$this->Byte($this->RGB[$xyoffset+R]*$contrast+$brightness);
					$this->RGB[$xyoffset+G]=$this->Byte($this->RGB[$xyoffset+G]*$contrast+$brightness);
					$this->RGB[$xyoffset+B]=$this->Byte($this->RGB[$xyoffset+B]*$contrast+$brightness);
				}
			}
		}

		//ACTUALIZAR RECURSO IMAGEN CON MATRIZ
		protected function update(){
			//Elimino el contenido anterior
			if(is_resource($this->img) && get_resource_type($this->img)=='gd') imagedestroy($this->img);
			//Creo una nueva imagen
			$this->img=imagecreatetruecolor($this->width,$this->height);
			imagealphablending($this->img, false);

			$offset=$this->height<<2; //Multiplico por 4 canales
			//Vuelco el contenido de la matriz
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					$r=$this->RGB[$xyoffset+R];	//rojo
					$g=$this->RGB[$xyoffset+G];	//verde
					$b=$this->RGB[$xyoffset+B];	//azul
					$a=$this->RGB[$xyoffset+A];	//0: opaco, 127: transparente
					$colors=imagecolorallocatealpha($this->img,$r,$g,$b,$a);
					imagesetpixel($this->img,$x,$y,$colors);
				}
			}
			imagesavealpha($this->img, true);
		}

		//MOSTRAR EN HTML
		public function show(){
			//Actualizo
			$this->update();
			//Creo el img HTML
			$this->imgdraw($this->img);
		}

		//MOSTRAR SOLA
		public function draw(){
			//Actualizo
			$this->update();
			//Elijo función
			$foo='image'.self::extension($this->type);
			//Libero el flujo de datos
			header('Content-Type: image/png');
			$foo($newimg);
			exit(); //Una vez enviada la imagen no puedo continuar
		}

		//Espacio de color: de RGB a YCbCr
		public static function rgbToYcbcr($r,$g,$b,&$y,&$cb,&$cr){
			$y=self::Byte(0.299*$r+0.587*$g+0.114*$b);
			$cb=self::Byte(-0.1687*$r-0.3313*$g+0.5*$b+128);
			$cr=self::Byte(0.5*$r-0.4187*$g-0.0813*$b+128);
		}

		//Espacio de color: de YCbCr a RGB
		public static function rgbFromYcbcr(&$r,&$g,&$b,$y,$cb,$cr){
			$r=self::Byte($y+1.402*($cr-128));
			$g=self::Byte($y-0.3441*($cb-128)-0.7141*($cr-128));
			$b=self::Byte($y+1.772*($cb-128));
		}

		//Transformación a un array YCbCr
		protected function getArrayYCbCr(){
			//Creo un array YCbCr desde mi array RGB
			//$YCbCr=array_fill(0,$this->width,array_fill(0,$this->height,array('Y'=>0,'Cb'=>0,'Cr'=>0)));
			//$YCbCr=new SplFixedArray($this->width*$this->height*4);
			//$YCbCr=array_fill(0,$this->width*$this->height*4,0);
			$this->initYCbCr();

			$offset=$this->height<<2; //Multiplico por 4 canales
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Obtengo la Luminancia mediante la transformación	//self::rgbToYcbcr($this->RGB[$xyoffset+R],$this->RGB[$xyoffset+G],$this->RGB[$xyoffset+B],$this->YCbCr[$xyoffset+Y],$this->YCbCr[$xyoffset+Cb],$this->YCbCr[$xyoffset+Cr]);
					self::rgbToYcbcr($this->RGB[$xyoffset+R],$this->RGB[$xyoffset+G],$this->RGB[$xyoffset+B],$Y,$Cb,$Cr);
					$this->YCbCr[$xyoffset+Y]=$Y;
					$this->YCbCr[$xyoffset+Cb]=$Cb;
					$this->YCbCr[$xyoffset+Cr]=$Cr;
				}
			}
		}

		//Transformación desde un array YCbCr
		protected function setArrayYCbCr(){

			//Vuelvo un espacio de color YCbCr en mi array RGB
			$offset=$this->height<<2; //Multiplico por 4 canales
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);	//self::rgbFromYcbcr($this->RGB[$xyoffset+R],$this->RGB[$xyoffset+G],$this->RGB[$xyoffset+B],$this->YCbCr[$xyoffset+Y],$this->YCbCr[$xyoffset+Cb],$this->YCbCr[$xyoffset+Cr]);
					self::rgbFromYcbcr($R,$G,$B,$this->YCbCr[$xyoffset+Y],$this->YCbCr[$xyoffset+Cb],$this->YCbCr[$xyoffset+Cr]);
					$this->RGB[$xyoffset+R]=$R;
					$this->RGB[$xyoffset+G]=$G;
					$this->RGB[$xyoffset+B]=$B;
				}
			}
		}

		//Calculo la frecuencia de valores para un canal de un array de imagen
		protected function frequency($array,$channel){
			//Creo un array
			$hist=array_fill(0,256,0);

			$offset=$this->height<<2; //Multiplico por 4 canales
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Acumulo valores iguales del canal argumento
					//++$hist[$array[$xyoffset+$channel]];
					$valor=$array[$xyoffset+$channel];
					$hist[$valor]=$hist[$valor]+1;
				}
			}
			return $hist;
		}

		//Calcula el valor más bajo presente en un histograma
		protected function histMin($hist){
			for($i=0;$i<256;++$i){
				//Si el valor tiene al menos 1 pixel
				if($hist[$i]){
					//Primer valor presente en el histograma
					return [$i,$hist[$i]];
				}
			}
		}

		//Calcula el valor más alto presente en un histograma
		protected function histMax($hist){
			for($i=255;$i>=0;--$i){
				//Si el valor tiene al menos 1 pixel
				if($hist[$i]){
					//Último valor presente en el histograma
					return [$i,$hist[$i]];
				}
			}
		}

		//ECUALIZAR
		public function equalize(){
			//Creo un nuevo array temporal para transformar el espacio de color
			$this->getArrayYCbCr();

			//Creo un histograma para el canal de la luminancia
			$histY=$this->frequency($this->YCbCr,Y);

			//Mínimo valor de la Función de Distribución Acumulada
			//[$i,$min]=$this->histMin($histY);	//PHP 7.2+
			list($i,$min)=$this->histMin($histY);
			//Cantidad total de pixeles
			$total=array_sum($histY); //$pixeles=$this->width*$this->height;
			//Acumulador (Refleja los valores parciales de la Función de Distribución Acumulada)
			$accum=0;
			//Recorro los valores del histograma desde el primero no cero
			for($i=$i;$i<256;++$i){
				//Si el valor no está presente en la imagen continuo
				if($histY[$i]){
					//pixeles acumulados hasta el valor actual del histograma
					$accum+=$histY[$i]; //$cdf[$i]=array_sum(array_slice($histY,0,$i+1));
					//Valor al que correspondería en una Función de Distribución lineal
					$eq[$i]=round(($accum-$min)/($total-$min)*(256-1));
				}
			}

			//La función "eq" me indica a que nuevo valor debería desplazar cada valor existente del histograma
			$offset=$this->height<<2; //Multiplico por 4 canales
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Cambio los valores de Luminancia en función de ese desplazamiento
					$this->YCbCr[$xyoffset+Y]=$eq[$this->YCbCr[$xyoffset+Y]];
				}
			}

			//Vuelvo a transformar el espacio de color para reflejar la ecualización en los canales RGB
			$this->setArrayYCbCr();

			//Libero memoria
			unset($this->YCbCr);
		}

		//RANGO DINAMICO
		public function range($newMin,$newMax){
			//Creo un nuevo array temporal para transformar el espacio de color
			$this->getArrayYCbCr();

			//Creo un histograma para el canal de la luminancia
			$histY=$this->frequency($this->YCbCr,Y);

			//Posición del menor valor del histograma
			//[$min,$val]=$this->histMin($histY);	//PHP 7.2+
			list($min,$val)=$this->histMin($histY);
			//Posición del mayor valor del histograma
			//[$max,$val]=$this->histMax($histY);	//PHP 7.2+
			list($max,$val)=$this->histMax($histY);

			//Calculo a que nuevo valor debería desplazar cada valor existente del histograma
			$offset=$this->height<<2; //Multiplico por 4 canales
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Cambio los valores de Luminancia en función de ese desplazamiento
					$this->YCbCr[$xyoffset+Y]=$this->Byte(($this->YCbCr[$xyoffset+Y]-$min)*($newMax-$newMin)/($max-$min)+$newMin);
				}
			}

			//Vuelvo a transformar el espacio de color para reflejar la ecualización en los canales RGB
			$this->setArrayYCbCr();

			//Libero memoria
			unset($this->YCbCr);
		}

		//HISTOGRAMA
		public function histogram($linked=FALSE,$sqrt=FALSE){

			//Calculo el histograma de cada color
			$histR=$this->frequency($this->RGB,R);
			$histG=$this->frequency($this->RGB,G);
			$histB=$this->frequency($this->RGB,B);

			//Dibujo el histograma de 128 pixeles de altura
			$height=128;
			$newimg=imagecreatetruecolor(256,$height);
			//Calculo los máximos para normalizar el eje de altura
			$maxr=max($histR);
			$maxg=max($histG);
			$maxb=max($histB);
			//Maximo de los 3 canales
			$max=max($maxr,$maxg,$maxb);
			if($linked){
				//Vinculo los 3 histogramas
				$maxr=$maxg=$maxb=$max;
			}
			//Pone a escala el eje vertical del histograma
			if(!function_exists("scale")){
				function scale($value,$max,$height,$scale=FALSE){
					$result=$value/$max; //normalización (valores entre 0 y 1)
					$result=$scale?$result**0.5:$result; //Escala raíz cuadrada (potencia valores bajos)
					$result=$height-round($result*$height); //Valor conforme a la altura en pixeles de la imagen
					return $result;
				}
			}

			//Color de cada canal no tan intenso
			$top=220;
			for($x=0;$x<256;++$x){
				//Si la columna de pixeles de la imagen tiene la altura necesaria
				$scaleR=scale($histR[$x],$maxr,$height,$sqrt);
				$scaleG=scale($histG[$x],$maxr,$height,$sqrt);
				$scaleB=scale($histB[$x],$maxr,$height,$sqrt);
				for($y=0;$y<$height;++$y){
					//Pinto con el color correspondiente
					$r=$y>$scaleR?$top:0;
					$g=$y>$scaleG?$top:0;
					$b=$y>$scaleB?$top:0;
					$colors=imagecolorallocate($newimg,$r,$g,$b);
					imagesetpixel($newimg,$x,$y,$colors);
				}
			}

			//Muestro el histograma
			$this->imgdraw($newimg);
		}

		//NEGATIVO
		public function negative(){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Por cada canal de cada pixel del array
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Invierto valores
					$this->RGB[$xyoffset+R]=255-$this->RGB[$xyoffset+R];
					$this->RGB[$xyoffset+G]=255-$this->RGB[$xyoffset+G];
					$this->RGB[$xyoffset+B]=255-$this->RGB[$xyoffset+B];
				}
			}
		}

		//OPACIDAD
		public function opacity($rel){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Por cada canal de cada pixel del array
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Seteo Alpha
					$this->RGB[$xyoffset+A]=$this->Byte(128*$rel,128);
				}
			}
		}

		//GAMA
		public function gamma($rel,$Y=FALSE){
			//Método en canales RGB
			if(!$Y){
				$offset=$this->height<<2; //Multiplico por 4 canales
				//Por cada canal de cada pixel del array
				for($x=0;$x<$this->width;++$x){
					$xoffset=$x*$offset;
					for($y=0;$y<$this->height;++$y){
						$xyoffset=$xoffset+($y<<2);
						//Aplico potencia
						$this->RGB[$xyoffset+R]=$this->Byte(255*($this->RGB[$xyoffset+R]/255)**(1/$rel));
						$this->RGB[$xyoffset+G]=$this->Byte(255*($this->RGB[$xyoffset+G]/255)**(1/$rel));
						$this->RGB[$xyoffset+B]=$this->Byte(255*($this->RGB[$xyoffset+B]/255)**(1/$rel));
					}
				}
			}
			//Método usando luminancia
			else{
				//Creo un nuevo array temporal para transformar el espacio de color
				$this->getArrayYCbCr();

				//Para cada pixel de la imagen
				$offset=$this->height<<2; //Multiplico por 4 canales
				for($x=0;$x<$this->width;++$x){
					$xoffset=$x*$offset;
					for($y=0;$y<$this->height;++$y){
						$xyoffset=$xoffset+($y<<2);
						//Aplico potencia al canal de Luminancia
						$this->YCbCr[$xyoffset+Y]=$this->Byte(255*($this->YCbCr[$xyoffset+Y]/255)**(1/$rel));
					}
				}

				//Vuelvo a transformar el espacio de color para reflejar la ecualización en los canales RGB
				$this->setArrayYCbCr();

				//Libero memoria
				unset($this->YCbCr);
			}
		}

		//ESCALA DE GRISES
		public function gray($Y=FALSE){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Por cada canal de cada pixel del array
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					if($Y){
						//Método usando luminancia percibida
						$I=sqrt(0.2126*$this->RGB[$xyoffset+R]**2+0.7152*$this->RGB[$xyoffset+G]**2+ 0.0722*$this->RGB[$xyoffset+B]**2);
					}else{
						//Método usando promedio de intensidad de canales RGB
						$I=round(($this->RGB[$xyoffset+R]+$this->RGB[$xyoffset+G]+$this->RGB[$xyoffset+B])/3);
					}
					$this->RGB[$xyoffset+R]=$I;
					$this->RGB[$xyoffset+G]=$I;
					$this->RGB[$xyoffset+B]=$I;
				}
			}
		}

		//BLANCO Y NEGRO
		public function binary($val,$Y=FALSE){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Por cada canal de cada pixel del array
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					if($Y){
						//Método usando luminancia percibida
						$I=sqrt(0.2126*$this->RGB[$xyoffset+R]**2+0.7152*$this->RGB[$xyoffset+G]**2+ 0.0722*$this->RGB[$xyoffset+B]**2);
					}else{
						//Método usando promedio de intensidad de canales RGB
						$I=round(($this->RGB[$xyoffset+R]+$this->RGB[$xyoffset+G]+$this->RGB[$xyoffset+B])/3);
					}
					//Si la intensidad supera el umbral es blanco, si no es negro
					$this->RGB[$xyoffset+R]=$I>=$val?255:0;
					$this->RGB[$xyoffset+G]=$I>=$val?255:0;
					$this->RGB[$xyoffset+B]=$I>=$val?255:0;
				}
			}
		}

		//SELECCION DE CANALES
		public function channel($channel){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Por cada canal de cada pixel del array
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Conservo unicamente un canal
					$this->RGB[$xyoffset+R]=$this->RGB[$xyoffset+$channel];
					$this->RGB[$xyoffset+G]=$this->RGB[$xyoffset+$channel];
					$this->RGB[$xyoffset+B]=$this->RGB[$xyoffset+$channel];
				}
			}
		}

		//COPIA DEL OBJETO
		public function clone(){
			//Copias automáticas

				//return clone $this;					//Este metodo hace algunas referencias. No funciona con SplFixedArrays

				//return unserialize(serialize($this)); //Este método evita las referencias, pero igual no funciona con SplFixedArrays

			//Copias manuales

				//Usando el recurso GD (tarda más tiempo)
				$class=get_called_class();
				//$img=new $class($this->filename);

				//Sin usar el recurso GD
				$img=new $class();
					$img->type=$this->type;
					$img->width=$this->width;
					$img->height=$this->height;
					$img->filename=$this->filename;

				$offset=$this->height<<2; //Multiplico por 4 canales
				//Actualizo con una copia manual del array
				for($x=0;$x<$this->width;++$x){
					$xoffset=$x*$offset;
					for($y=0;$y<$this->height;++$y){
						$xyoffset=$xoffset+($y<<2);
						//Cada una de las capas
						for($z=0;$z<4;++$z){
							//Logro $img->RGB=$this->RGB
							$img->RGB[$xyoffset+$z]=$this->RGB[$xyoffset+$z];
						}
					}
				}

				//Regreso el objeto
				return $img;
		}

		//TRANSPARENCIA NO UNIFORME
		public function alpha($img){
			//Obtengo la luminancia en caso de que no sea una máscara binaria
			$img->getArrayYCbCr();

			$offset=$this->height<<2; //Multiplico por 4 canales
			//Aplico la máscara al canal alpha
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					//Niego la luminancia y la dividido para que 255 sea opaco y 0 transparente
					$this->RGB[$xyoffset+A]=floor((255-$img->YCbCr[$xyoffset+Y])/2);
				}
			}
			//Libero memoria
			unset($this->YCbCr);
		}

		//SUMA
		public function add($img){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Sumo los canales de ambas imágenes
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					for($z=0;$z<4;++$z){
						$this->RGB[$xyoffset+$z]=$this->Byte($this->RGB[$xyoffset+$z]+$img->RGB[$xyoffset+$z]);
					}
				}
			}
		}

		//ENMASCARADO
		public function mask($img){
			$offset=$this->height<<2; //Multiplico por 4 canales
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					for($z=0;$z<4;++$z){
						$this->RGB[$xyoffset+$z]=$this->byte($this->RGB[$xyoffset+$z]*$img->RGB[$xyoffset+$z]/255);
					}
				}
			}
		}

		//COMBINACION
		public function blit($img){
			//Creo una máscara con la imagen recibida
			$mask=$img->clone($img);
			//Todo color no negro es blanco
			$mask->binary(1);
			//Vuelvo blanco el negro
			$mask->negative();
			//Aplico la mascara
			$this->mask($mask);
			//Combino con la imagen recibida
			$this->add($img);
		}

		//PROMEDIO DE IMAGENES
		public function average($img){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Sumo los canales de ambas imágenes y los divido a la mitad
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					for($z=0;$z<4;++$z){
						$this->RGB[$xyoffset+$z]=$this->Byte(($this->RGB[$xyoffset+$z]+$img->RGB[$xyoffset+$z])/2);
					}
				}
			}
		}

		//Cambia un color por otro
		public function newColor($r,$g,$b,$nr,$ng,$nb){
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Recorro los pixeles
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					if($this->XYisColor($x,$y,$r,$g,$b)){
						$this->RGB[$xyoffset+R]=$nr;
						$this->RGB[$xyoffset+G]=$ng;
						$this->RGB[$xyoffset+B]=$nb;
					}
				}
			}
		}

		//FILTRO
		public function filter($filter,$arg=NULL){
			//Creo una imagen donde volcar los datos
			$class=get_called_class();
			$img=new $class($this->filename);
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Los limites del filtro excluirán los bordes dentro de la función $filter
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					for($z=0;$z<3;++$z){
						$img->RGB[$xyoffset+$z]=$this->$filter($x,$y,$z,$arg);
					}
				}
			}
			//Vuelco los datos al terminar el proceso
			for($x=0;$x<$this->width;++$x){
				$xoffset=$x*$offset;
				for($y=0;$y<$this->height;++$y){
					$xyoffset=$xoffset+($y<<2);
					for($z=0;$z<3;++$z){
						$this->RGB[$xyoffset+$z]=$img->RGB[$xyoffset+$z];
					}
				}
			}
		}

		//Pasa bajos
		protected function lowpass($x,$y,$c,$size){
			$result=0;
			//Las dimensiones del filtro tienen que conservar al pixel en el centro
			if(!$size%2) throw new Exception('Las dimensiones del filtro no son impares');
			//En principio es el tamaño del filtro al cuadrado
			$denominator=$size**2;
			//No puedo aplicar el filtro al borde
			$edge=floor($size/2);
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Contemplo todos los vecinos
			for($i=-$edge;$i<=$edge;++$i){
				$xoffset=($x+$i)*$offset;
				for($j=-$edge;$j<=$edge;++$j){
					$xyoffset=$xoffset+(($y+$j)<<2);
					//isset no funciona con SplFixedArray
					//!==NULL no funciona con arrays nativos
					if($this->isInside($x+$i,$y+$j)){
						//$result+=$this->RGB[$x+$i][$y+$j][$c];
						$result+=$this->RGB[$xyoffset+$c];
					}else{
						//El pixel está más alla del borde, no sumará en el denominador
						--$denominator;
					}
				}
			}

			//Normalizo
			return round($result/$denominator);
		}

		//Mediana
		protected function median($x,$y,$c,$size){
			$list=array();
			//Las dimensiones del filtro tienen que conservar al pixel en el centro
			if(!$size%2) throw new Exception('Las dimensiones del filtro no son impares');
			//No puedo aplicar el filtro al borde
			$edge=floor($size/2);
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Contemplo todos los vecinos
			for($i=-$edge;$i<=$edge;++$i){
				$xoffset=($x+$i)*$offset;
				for($j=-$edge;$j<=$edge;++$j){
					$xyoffset=$xoffset+(($y+$j)<<2);
					if($this->isInside($x+$i,$y+$j)){
						//$list[]=$this->RGB[$x+$i][$y+$j][$c];
						$list[]=$this->RGB[$xyoffset+$c];
					}
				}
			}
			//cantidad de pixeles
			$n=count($list);
			//ordeno la lista
			sort($list);
			//Calculo mediana dependiendo de si los pixeles son pares o impares
			return $n%2? $list[$n/2-.5]: round(($list[$n/2]+$list[$n/2-1])/2);
		}

		//Guarda simplificando la conversión de coordenadas 3D en unidemensionales
		protected function setRGB($x,$y,$c,$int){
			$offset=$this->height<<2; //Multiplico por 4 canales
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			$this->RGB[$xyoffset+$c]=$int;
		}

		//Lee simplificando la conversión de coordenadas 3D en unidemensionales
		protected function getRGB($x,$y,$c){
			$offset=$this->height<<2; //Multiplico por 4 canales
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			return $this->RGB[$xyoffset+$c];
		}

		//Erosionar
		protected function erode($x,$y,$c,$size){
			//Las dimensiones del filtro tienen que conservar al pixel en el centro
			if(!$size%2) throw new Exception('Las dimensiones del filtro no son impares');
			//No puedo aplicar el filtro al borde
			$edge=floor($size/2);
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Contemplo todos los vecinos
			for($i=-$edge;$i<=$edge;++$i){
				$xoffset=($x+$i)*$offset;
				for($j=-$edge;$j<=$edge;++$j){
					$xyoffset=$xoffset+(($y+$j)<<2);
					if($this->isInside($x+$i,$y+$j)){
						//Si hay al menos un vecino 0, erosiono
						//if($this->RGB[$x+$i][$y+$j][$c]==0) return 0;
						if($this->RGB[$xyoffset+$c]==0) return 0;
					}
				}
			}
			//No hubo cambios
			//return $this->RGB[$x][$y][$c];
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			return $this->RGB[$xyoffset+$c];
		}

		//Erosionar
		protected function dilate($x,$y,$c,$size){
			//Las dimensiones del filtro tienen que conservar al pixel en el centro
			if(!$size%2) throw new Exception('Las dimensiones del filtro no son impares');
			//No puedo aplicar el filtro al borde
			$edge=floor($size/2);
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Contemplo todos los vecinos
			for($i=-$edge;$i<=$edge;++$i){
				$xoffset=($x+$i)*$offset;
				for($j=-$edge;$j<=$edge;++$j){
					$xyoffset=$xoffset+(($y+$j)<<2);
					if($this->isInside($x+$i,$y+$j)){
						//Si hay al menos un vecino no 0, dilato
						//if($this->RGB[$x+$i][$y+$j][$c]!=0) return 255;
						if($this->RGB[$xyoffset+$c]!=0) return 255;
					}
				}
			}
			//No hubo cambios
			//return $this->RGB[$x][$y][$c];
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			return $this->RGB[$xyoffset+$c];
		}

		//Derivada 1ra (si es gaussiana se llama Sobel)
		protected function prewitt($x,$y,$c,$gauss=0){
			//Si no estoy en los pixeles del borde
			if($this->isInside($x-1,$y-1)&&$this->isInside($x+1,$y+1)){
				//Derivada horizontal
				/*$gx=-1*$this->RGB[$x-1][$y-1][$c]+
					-(1+$gauss)*$this->RGB[$x-1][$y][$c]+
					-1*$this->RGB[$x-1][$y+1][$c]+
					+1*$this->RGB[$x+1][$y-1][$c]+
					+(1+$gauss)*$this->RGB[$x+1][$y][$c]+
					+1*$this->RGB[$x+1][$y+1][$c];*/
				$gx=-1*$this->getRGB($x-1,$y-1,$c)+
					-(1+$gauss)*$this->getRGB($x-1,$y,$c)+
					-1*$this->getRGB($x-1,$y+1,$c)+
					+1*$this->getRGB($x+1,$y-1,$c)+
					+(1+$gauss)*$this->getRGB($x+1,$y,$c)+
					+1*$this->getRGB($x+1,$y+1,$c);
				//Derivada vertical
				/*$gy=-1*$this->RGB[$x-1][$y-1][$c]+
					-(1+$gauss)*$this->RGB[$x][$y-1][$c]+
					-1*$this->RGB[$x+1][$y-1][$c]+
					+1*$this->RGB[$x-1][$y+1][$c]+
					+(1+$gauss)*$this->RGB[$x][$y+1][$c]+
					+1*$this->RGB[$x+1][$y+1][$c];*/
				$gy=-1*$this->getRGB($x-1,$y-1,$c)+
					-(1+$gauss)*$this->getRGB($x,$y-1,$c)+
					-1*$this->getRGB($x+1,$y-1,$c)+
					+1*$this->getRGB($x-1,$y+1,$c)+
					+(1+$gauss)*$this->getRGB($x,$y+1,$c)+
					+1*$this->getRGB($x+1,$y+1,$c);
				//Sumo magnitudes
				return $this->Byte(sqrt($gx**2+$gy**2));
			}else return 0;
		}

		//Derivada 2da en 4 u 8 direcciones
		protected function laplace($x,$y,$c,$diagonal=FALSE){
			$result=0;
			//El centro se resta inicialmente 4 u 8 veces
			$centro=$diagonal?-8:-4; //El peso del pixel central
			$offset=$this->height<<2; //Multiplico por 4 canales
			//Contemplo los vecinos involucrados
			for($i=-1;$i<=1;++$i){
				$xoffset=($x+$i)*$offset;
				for($j=-1;$j<=1;++$j){
					$xyoffset=$xoffset+(($y+$j)<<2);
					if($diagonal){
						$peso1=(0!=$i)||(0!=$j); //Solo excluyo el centro
					}else{
						//Solo cardinales, excluyo diagonales
						$peso1=abs($i)!=abs($j);
					}
					if($peso1){
						if($this->isInside($x+$i,$y+$j)){
							$result+=$this->RGB[$xyoffset+$c];
						}else{
							//El pixel está más allá del borde, no se contrarresta
							++$centro;
						}
					}
				}
			}
			//Resto el centro tantas veces como pixeles involucrados
			//return $this->Byte($result+$centro*$this->RGB[$x][$y][$c]);
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			return $this->Byte($result+$centro*$this->RGB[$xyoffset+$c]);
		}

		//Pasa bajos por diferencia
		protected function unsharp($x,$y,$c,$size=3){
			//Calculo la intensidad de frecuencias bajas
			$low=$this->lowpass($x,$y,$c,$size);
			//El resultado es la imagen + la intensidad de frecuencias altas
			//return $this->Byte(2*$this->RGB[$x][$y][$c]-$low);
			$offset=$this->height<<2; //Multiplico por 4 canales
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			return $this->Byte(2*$this->RGB[$xyoffset+$c]-$low);
		}

		//Dentro del array
		protected function isInside($x,$y){
			//Comprueba si la coordenada está dentro de los límites del array
			return $x>=0 && $x<$this->width && $y>=0 && $y<$this->height;
		}

		//CONTAR PIXELES
		public function area($r,$g,$b){
			//Inicializo el contador
			$count=0;
			//Recorro la imagen
			for($x=0;$x<$this->width;++$x){
				for($y=0;$y<$this->height;++$y){
					if($this->XYisColor($x,$y,$r,$g,$b)){
						++$count;
					}
				}
			}
			//Formato de porcentaje con 4 decimales
			return number_format(100*$count/($this->height*$this->width),4)."%";
		}

		//CONTAR OBJETOS
		public function count($r,$g,$b){
			if(!$r && !$g && !$b) throw new Exception('No puedo contar el color negro');
			//Creo una copia editable de la imagen
			$img=$this->clone();
			//Inicializo el contador
			$count=0;
			//Recorro la imagen
			for($x=0;$x<$img->width;++$x){
				for($y=0;$y<$img->height;++$y){
					if($img->XYisColor($x,$y,$r,$g,$b)){
						++$count;
						$img->fill($x,$y,$r,$g,$b,0,0,0);
					}
				}
			}
			return $count;
		}

		//Si el pixel en XY tiene un dado color
		protected function XYisColor($x,$y,$r,$g,$b){
			$offset=$this->height<<2; //Multiplico por 4 canales
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			//Si es un pixel valido compruebo el color
			return ($this->isInside($x,$y) &&
			   $this->RGB[$xyoffset+R]==$r &&
			   $this->RGB[$xyoffset+G]==$g &&
			   $this->RGB[$xyoffset+B]==$b)?TRUE:FALSE;
		}

		//PINTAR RECURSIVO
		public function fill($x,$y,$r,$g,$b,$nr,$ng,$nb){
			if($this->getHeight()*$this->getWidth()>ini_get('xdebug.max_nesting_level')){
				//Evito la recursividad
				$this->fillq($x,$y,$r,$g,$b,$nr,$ng,$nb);
				return;
			}
			$offset=$this->height<<2; //Multiplico por 4 canales
			$xoffset=$x*$offset;
			$xyoffset=$xoffset+($y<<2);
			$this->RGB[$xyoffset+R]=$nr;
			$this->RGB[$xyoffset+G]=$ng;
			$this->RGB[$xyoffset+B]=$nb;
			if($this->XYisColor($x-1,$y,$r,$g,$b))
				$this->fill($x-1,$y,$r,$g,$b,$nr,$ng,$nb);
			if($this->XYisColor($x+1,$y,$r,$g,$b))
				$this->fill($x+1,$y,$r,$g,$b,$nr,$ng,$nb);
			if($this->XYisColor($x,$y-1,$r,$g,$b))
				$this->fill($x,$y-1,$r,$g,$b,$nr,$ng,$nb);
			if($this->XYisColor($x,$y+1,$r,$g,$b))
				$this->fill($x,$y+1,$r,$g,$b,$nr,$ng,$nb);
		}

		//PINTAR ENCOLADO
		public function fillq($x,$y,$r,$g,$b,$nr,$ng,$nb){
			//Agrego el pixel a la cola
			$cola=array();
			$m0=memory_get_usage();
			$cola["$x,$y"]=true; //248Bytes
			$m1=memory_get_usage();
			if(($m1-$m0)*$this->getHeight()*$this->getWidth()>1024*1024*intval(ini_get('memory_limit'))) die("Limite de memoria");
			$offset=$this->height<<2; //Multiplico por 4 canales
			for($seguir=true;$seguir;$seguir=next($cola)){
				$key=key($cola);
				$comapos=strpos($key,',');
				$x=substr($key,0,$comapos);
				$y=substr($key,$comapos+1);
				$xoffset=$x*$offset;
				$xyoffset=$xoffset+($y<<2);
				$this->RGB[$xyoffset+R]=$nr;
				$this->RGB[$xyoffset+G]=$ng;
				$this->RGB[$xyoffset+B]=$nb;
				if($this->XYisColor($x-1,$y,$r,$g,$b))
					$cola[($x-1).",$y"]=true;
				if($this->XYisColor($x+1,$y,$r,$g,$b))
					$cola[($x+1).",$y"]=true;
				if($this->XYisColor($x,$y-1,$r,$g,$b))
					$cola["$x,".($y-1)]=true;
				if($this->XYisColor($x,$y+1,$r,$g,$b))
					$cola["$x,".($y+1)]=true;
			}
		}

		//PENDIENTES:
		//	-Espejar
		//	-Recortar
		//	-Cambiar tamaño
		//	-Rotar

	}//Fin de la clase

	//Testing
	function test(){

		//Nombre del archivo
		$filename='image.png';

		//Creo la imagen
		$time=-microtime(true);
		$startMemory=memory_get_usage();
		$img=new imageArray($filename);

		$time+=microtime(true); //Elapsed time
		echo "<p>Imagen cargada usando arrays de largo fijo: ".number_format((memory_get_usage()-$startMemory)/1024/1024,2)." MB en ".sprintf("%.2f", $time)." segundos.</p>";
		unset($img);

		$time=-microtime(true);
		$startMemory=memory_get_usage();
		$img=new imageArray($filename);

		$time+=microtime(true); //Elapsed time
		echo "<p>Imagen cargada usando arrays nativos: ".number_format((memory_get_usage()-$startMemory)/1024/1024,2)." MB en ".sprintf("%.2f", $time)." segundos.</p>";

		echo "<h3>Imagen</h3>";
		$img->show();
		$img->histogram(TRUE,TRUE);

		//PROCESOS
		$y=round($img->getHeight()*0.724);
		echo "<h3>Perfil en y=$y</h3>";			$img->profile($y);
		echo "<h3>Brillo +25</h3>";				$img->CandB(1,25);			$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Contraste al 50%</h3>";		$img->CandB(0.5);			$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Rango de 50 a 100</h3>";		$img->range(50,100);		$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Clono la imagen</h3>";		$img2=$img->clone();
		echo "<h3>Ecualización</h3>";			$img->equalize();			$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Gama 0.5</h3>";				$img->gamma(0.5);			$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Grises</h3>";					$img->gray(TRUE);			$img->show();	$img->histogram(TRUE,TRUE);
		//Recargo para deshacer procesos previos
		echo "<h3>Recargando...</h3>";			$img->reload();				$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Canal Azul</h3>";				$img->channel(B);			$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Binario 34</h3>";				$img->binary(34);			$img->show();

		echo "<h3>Utilizo el clon...</h3>";

		echo "<h3>Opacidad 50%</h3>";			$img2->opacity(0.5);		$img2->show();	$img2->histogram(TRUE,TRUE);
		//Recargo para deshacer procesos previos
		echo "<h3>Recargando...</h3>";			$img2->reload();			$img2->show();	$img2->histogram(TRUE,TRUE);
		$img->negative();	//Mascara que elimina brillos
		echo "<h3>Aplico Alpha</h3>";			$img2->alpha($img);			$img2->show();	$img2->histogram(TRUE,TRUE);
		//Recargo para deshacer procesos previos
		echo "<h3>Recargando...</h3>";			$img2->reload();			$img2->show();	$img2->histogram(TRUE,TRUE);
		echo "<h3>Negativo</h3>";				$img2->negative();			$img2->show();	$img2->histogram(TRUE,TRUE);
		$img->negative();	//Mascara que conserva los brillos
		echo "<h3>Mascara</h3>";				$img2->mask($img);			$img2->show();	$img2->histogram(TRUE,TRUE);
		//Recargo para deshacer procesos previos
		echo "<h3>Recargando...</h3>";			$img->reload();				$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Combino</h3>";				$img->blit($img2);			$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Recargando...</h3>";			$img->reload();				$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Realce 3x3</h3>";				$img->filter('unsharp',3);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Recargando...</h3>";			$img->reload();				$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Realce 5x5</h3>";				$img->filter('unsharp',5);	$img->show();	$img->histogram(TRUE,TRUE);

		echo "<hr>"; //Nueva imagen
		$img=new imageArray('image_ruido.png');								$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Pasa bajos</h3>";				$img->filter('lowpass',3);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Recargando...</h3>";			$img->reload();				$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Mediana</h3>";				$img->filter('median',3);	$img->show();	$img->histogram(TRUE,TRUE);

		echo "<hr>"; //Nueva imagen
		echo "<h3>Nueva imagen</h3>";
		$img=new imageArray('image5.png');									$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Pasa bajos 9x9</h3>";			$img->filter('lowpass',9);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Binario 48</h3>";				$img->binary(48);			$img->show();
		echo "<h3>Cuento objetos</h3>";			echo "<p>Objetos: ".$img->count(255,255,255)."</p>";
		echo "<h3>Cuento superficie</h3>";		echo "<p>Área: ".$img->area(255,255,255)."</p>";

		echo "<hr>"; //Nueva imagen
		$img=new imageArray('image6.png');									$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Bordes 90°</h3>";				$img->filter('laplace',0);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Recargando...</h3>";			$img->reload();				$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Bordes 45°</h3>";				$img->filter('laplace',1);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Recargando...</h3>";			$img->reload();				$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Bordes Gruesos</h3>";			$img->filter('prewitt');	$img->show();	$img->histogram(TRUE,TRUE);

		echo "<hr>"; //Nueva imagen
		$img=new imageArray('image7.png');									$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Mediana</h3>";				$img->filter('median',3);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Binario 200</h3>";			$img->binary(200);			$img->show();
		echo "<h3>Erosiono</h3>";				$img->filter('erode',3);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Dilato</h3>";					$img->filter('dilate',3);	$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Elimino huecos</h3>";			$img->fill(0,0,255,255,255,128,128,128);
												$img->newColor(255,255,255,0,0,0);
												$img->newColor(128,128,128,255,255,255);
																			$img->show();	$img->histogram(TRUE,TRUE);
		echo "<h3>Mediana</h3>";				$img->filter('median',5);	$img->show();	$img->histogram(TRUE,TRUE);
	}

	//Mostrar procesos de la imagen dentro de etiquetas img de HTML
	header('Content-Type: text/html; charset=ISO-8859-1');
?>