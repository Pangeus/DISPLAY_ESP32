<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Display</title>
    <style>
        :root {
            --bg-color: #1e1e1e;
            --segment-off: #333;
            --text-color: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        /* Indicador de Conexión */
        .status-container {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            align-self: flex-start;
        }

        #status-dot {
            width: 15px;
            height: 15px;
            background-color: gray;
            border-radius: 50%;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }

        #status-text {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        /* Estilos del Display de 7 Segmentos */
        .display-container {
            position: relative;
            width: 250px;
            height: 400px;
        }

        .segment {
            fill: var(--segment-off);
            stroke: #000;
            stroke-width: 2;
            cursor: pointer;
            transition: fill 0.1s ease;
        }

        .segment:hover {
            opacity: 0.8;
        }

        /* Interfaz del Selector de Color */
        #color-picker-overlay {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            z-index: 100;
            flex-direction: column;
            gap: 15px;
        }

        .input-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        input[type="text"], input[type="number"] {
            background: #444;
            border: 1px solid #555;
            color: white;
            padding: 5px;
            border-radius: 4px;
            width: 60px;
        }

        .close-btn {
            background: #ff5f56;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-end;
        }
    </style>
</head>
<body>

    <div class="status-container">
        <div id="status-dot"></div>
        <span id="status-text">Disconnected</span>
    </div>

    <h1>Control de Display</h1>

    <div class="display-container">
        <svg viewBox="0 0 200 340" width="100%" height="100%">
            <polygon id="seg-a" class="segment" points="40,20 160,20 145,45 55,45" onclick="openPicker('a')" />
            <polygon id="seg-b" class="segment" points="165,30 185,50 185,150 160,135 160,45" onclick="openPicker('b')" />
            <polygon id="seg-c" class="segment" points="165,310 185,290 185,190 160,205 160,295" onclick="openPicker('c')" />
            <polygon id="seg-d" class="segment" points="40,320 160,320 145,295 55,295" onclick="openPicker('d')" />
            <polygon id="seg-e" class="segment" points="35,310 15,290 15,190 40,205 40,295" onclick="openPicker('e')" />
            <polygon id="seg-f" class="segment" points="35,30 15,50 15,150 40,135 40,45" onclick="openPicker('f')" />
            <polygon id="seg-g" class="segment" points="45,170 155,170 145,185 55,185 45,170 55,155 145,155" onclick="openPicker('g')" />
        </svg>
    </div>

    <div id="color-picker-overlay">
        <button class="close-btn" onclick="closePicker()">X</button>
        <div class="input-group">
            <label>Visual:</label>
            <input type="color" id="main-picker" oninput="updateFromVisual(this.value)">
        </div>
        <div class="input-group">
            <label>HEX:</label>
            <input type="text" id="hex-input" placeholder="#FFFFFF" onchange="updateFromHex(this.value)">
        </div>
        <div class="input-group">
            <label>RGB:</label>
            <input type="number" id="r-input" min="0" max="255" placeholder="R" oninput="updateFromRGB()">
            <input type="number" id="g-input" min="0" max="255" placeholder="G" oninput="updateFromRGB()">
            <input type="number" id="b-input" min="0" max="255" placeholder="B" oninput="updateFromRGB()">
        </div>
    </div>

    <script>
        let socket;
        let currentSegment = null;
        // Cambia esta IP por la que asigne tu router al ESP32
        const gateway = `ws://${window.location.hostname}/ws`; 

        // Inicializar WebSocket
        function initWebSocket() {
            console.log('Intentando conectar...');
            socket = new WebSocket(gateway);
            
            socket.onopen = () => {
                document.getElementById('status-dot').style.backgroundColor = '#2ecc71';
                document.getElementById('status-text').innerText = 'Connected';
            };

            socket.onclose = () => {
                document.getElementById('status-dot').style.backgroundColor = 'gray';
                document.getElementById('status-text').innerText = 'Disconnected';
                setTimeout(initWebSocket, 2000); // Reintento
            };
        }

        // Abrir Interfaz de color
        function openPicker(segmentId) {
            currentSegment = segmentId;
            document.getElementById('color-picker-overlay').style.display = 'flex';
        }

        function closePicker() {
            document.getElementById('color-picker-overlay').style.display = 'none';
        }

        // Lógica de Sincronización de Colores
        function updateFromVisual(hex) {
            document.getElementById('hex-input').value = hex.toUpperCase();
            const rgb = hexToRgb(hex);
            document.getElementById('r-input').value = rgb.r;
            document.getElementById('g-input').value = rgb.g;
            document.getElementById('b-input').value = rgb.b;
            applyColor(hex, rgb);
        }

        function updateFromHex(hex) {
            if(/^#[0-9A-F]{6}$/i.test(hex)) {
                document.getElementById('main-picker').value = hex;
                const rgb = hexToRgb(hex);
                document.getElementById('r-input').value = rgb.r;
                document.getElementById('g-input').value = rgb.g;
                document.getElementById('b-input').value = rgb.b;
                applyColor(hex, rgb);
            }
        }

        function updateFromRGB() {
            const r = parseInt(document.getElementById('r-input').value) || 0;
            const g = parseInt(document.getElementById('g-input').value) || 0;
            const b = parseInt(document.getElementById('b-input').value) || 0;
            const hex = rgbToHex(r, g, b);
            document.getElementById('main-picker').value = hex;
            document.getElementById('hex-input').value = hex.toUpperCase();
            applyColor(hex, {r, g, b});
        }

        function applyColor(hex, rgb) {
            if (currentSegment) {
                // Cambiar color en la web
                document.getElementById(`seg-${currentSegment}`).style.fill = hex;
                
                // Enviar al ESP32 vía WebSocket
                if (socket && socket.readyState === WebSocket.OPEN) {
                    const data = {
                        seg: currentSegment,
                        r: rgb.r,
                        g: rgb.g,
                        b: rgb.b
                    };
                    socket.send(JSON.stringify(data));
                }
            }
        }

        // Helpers de conversión
        function hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        }

        function rgbToHex(r, g, b) {
            return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }

        window.onload = initWebSocket;
    </script>
</body>
</html>