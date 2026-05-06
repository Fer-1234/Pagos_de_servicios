from flask import Flask, render_template, request, jsonify, session, redirect, url_for
from flask_cors import CORS
import pymysql
import pymysql.cursors
import hashlib
import os
from datetime import datetime

app = Flask(__name__, template_folder='templates', static_folder='static')
app.secret_key = os.environ.get('SECRET_KEY', 'tu_clave_secreta_super_segura_123')
CORS(app)

# ============================================
# CONFIGURACION TiDB Cloud
# ============================================
DB_CONFIG = {
    'host': 'gateway01.us-east-1.prod.aws.tidbcloud.com',
    'port': 4000,
    'user': '3rwEX3bM6GgimVG.root',
    'password': 'HxpS7cifPX2bMUJ8',
    'database': 'escuela_pagos',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor,
    'ssl': {'ca': None}
}

def get_db_connection():
    return pymysql.connect(**DB_CONFIG)

# ============================================
# URL PUBLICA - RENDER
# ============================================
# Render automaticamente setea RENDER_EXTERNAL_URL
BASE_URL = os.environ.get('RENDER_EXTERNAL_URL', '')

# Si no esta en Render, usar variable manual o detectar
if not BASE_URL:
    BASE_URL = os.environ.get('BASE_URL', '')

TIENE_URL_PUBLICA = bool(BASE_URL and not 'localhost' in BASE_URL)

def get_base_url():
    if BASE_URL:
        return BASE_URL.rstrip('/')
    return request.url_root.rstrip('/')

# ============================================
# RUTAS - PAGINAS HTML
# ============================================

@app.route('/')
def index():
    if 'usuario_id' in session:
        return redirect(url_for('catalogo'))
    return render_template('index.html')

@app.route('/catalogo')
def catalogo():
    if 'usuario_id' not in session:
        return redirect(url_for('index'))
    return render_template('catalogo.html')

@app.route('/perfil')
def perfil():
    if 'usuario_id' not in session:
        return redirect(url_for('index'))
    return render_template('perfil.html')

@app.route('/compras')
def compras():
    if 'usuario_id' not in session:
        return redirect(url_for('index'))
    return render_template('compras.html')

@app.route('/pago')
def pago():
    if 'usuario_id' not in session:
        return redirect(url_for('index'))
    return render_template('pago.html')

@app.route('/pago_tarjeta')
def pago_tarjeta():
    if 'usuario_id' not in session:
        return redirect(url_for('index'))
    return render_template('pago_tarjeta.html')

@app.route('/exito')
def exito():
    if 'usuario_id' not in session:
        return redirect(url_for('index'))
    
    payment_id = request.args.get('payment_id', request.args.get('collection_id', ''))
    status = request.args.get('status', '')
    external_reference = request.args.get('external_reference', '')
    metodo = request.args.get('metodo', 'mercadopago')
    
    # Guardar pago si viene de Mercado Pago
    if payment_id and metodo == 'mercadopago':
        try:
            conn = get_db_connection()
            with conn.cursor() as cursor:
                producto_id = 0
                if external_reference and external_reference.startswith('PROD_'):
                    parts = external_reference.split('_')
                    if len(parts) >= 2:
                        try:
                            producto_id = int(parts[1])
                        except:
                            pass
                
                if producto_id == 0:
                    producto_id = session.get('producto_id_pendiente', 0)
                
                precio = 0
                if producto_id > 0:
                    cursor.execute("SELECT precio FROM productos WHERE id = %s", (producto_id,))
                    prod = cursor.fetchone()
                    if prod:
                        precio = float(prod['precio'])
                
                if producto_id > 0 and precio > 0:
                    cursor.execute(
                        """INSERT INTO pagos (usuario_id, producto_id, monto, estado, paypal_order_id, metodo_pago) 
                           VALUES (%s, %s, %s, 'completado', %s, %s)""",
                        (session['usuario_id'], producto_id, precio, payment_id, metodo)
                    )
                    conn.commit()
        except Exception as e:
            print(f"Error guardando pago: {e}")
        finally:
            if 'conn' in locals():
                conn.close()
    
    session.pop('producto_id_pendiente', None)
    return render_template('exito.html', payment_id=payment_id, status=status, metodo=metodo)

@app.route('/cancelado')
def cancelado():
    if 'usuario_id' not in session:
        return redirect(url_for('index'))
    session.pop('producto_id_pendiente', None)
    return render_template('cancelado.html')

# ============================================
# API - AUTENTICACION
# ============================================

@app.route('/api/login', methods=['POST'])
def api_login():
    data = request.get_json()
    usuario = data.get('usuario', '')
    password = data.get('password', '')
    password_hash = hashlib.md5(password.encode()).hexdigest()
    
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT * FROM usuarios WHERE usuario = %s AND password = %s", (usuario, password_hash))
            user = cursor.fetchone()
            
            if user:
                session['usuario_id'] = user['id']
                session['usuario_nombre'] = user['nombre']
                session['usuario_email'] = user['email']
                session['usuario_foto'] = user.get('foto', '')
                return jsonify({
                    'success': True,
                    'user': {
                        'id': user['id'],
                        'nombre': user['nombre'],
                        'email': user['email'],
                        'foto': user.get('foto', '')
                    }
                })
            else:
                return jsonify({'success': False, 'message': 'Usuario o contraseña incorrectos'})
    finally:
        conn.close()

@app.route('/api/registro', methods=['POST'])
def api_registro():
    data = request.get_json()
    nombre = data.get('nombre', '')
    email = data.get('email', '')
    usuario = data.get('usuario', '')
    password = data.get('password', '')
    
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT id FROM usuarios WHERE usuario = %s", (usuario,))
            if cursor.fetchone():
                return jsonify({'success': False, 'message': 'El usuario ya existe'})
            
            password_hash = hashlib.md5(password.encode()).hexdigest()
            cursor.execute(
                "INSERT INTO usuarios (nombre, email, usuario, password) VALUES (%s, %s, %s, %s)",
                (nombre, email, usuario, password_hash)
            )
            conn.commit()
            return jsonify({'success': True, 'message': 'Registro exitoso'})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})
    finally:
        conn.close()

@app.route('/api/logout', methods=['POST'])
def api_logout():
    session.clear()
    return jsonify({'success': True})

@app.route('/api/sesion')
def api_sesion():
    if 'usuario_id' in session:
        return jsonify({
            'loggedIn': True,
            'user': {
                'id': session['usuario_id'],
                'nombre': session['usuario_nombre'],
                'email': session['usuario_email'],
                'foto': session.get('usuario_foto', '')
            }
        })
    return jsonify({'loggedIn': False})

# ============================================
# API - PRODUCTOS
# ============================================

@app.route('/api/productos')
def api_productos():
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT * FROM productos")
            return jsonify(cursor.fetchall())
    finally:
        conn.close()

@app.route('/api/producto/<int:id>')
def api_producto(id):
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT * FROM productos WHERE id = %s", (id,))
            producto = cursor.fetchone()
            if producto:
                return jsonify(producto)
            return jsonify({'error': 'Producto no encontrado'}), 404
    finally:
        conn.close()

# ============================================
# API - COMPRAS
# ============================================

@app.route('/api/compras')
def api_compras():
    if 'usuario_id' not in session:
        return jsonify({'error': 'No autorizado'}), 401
    
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                """SELECT p.*, pr.nombre as producto_nombre, pr.descripcion as producto_desc 
                   FROM pagos p 
                   LEFT JOIN productos pr ON p.producto_id = pr.id 
                   WHERE p.usuario_id = %s 
                   ORDER BY p.fecha DESC""",
                (session['usuario_id'],)
            )
            return jsonify(cursor.fetchall())
    finally:
        conn.close()

# ============================================
# API - PERFIL
# ============================================

@app.route('/api/perfil')
def api_perfil():
    if 'usuario_id' not in session:
        return jsonify({'error': 'No autorizado'}), 401
    
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                "SELECT id, nombre, email, usuario, foto FROM usuarios WHERE id = %s",
                (session['usuario_id'],)
            )
            return jsonify(cursor.fetchone() or {})
    finally:
        conn.close()

@app.route('/api/perfil', methods=['POST'])
def api_actualizar_perfil():
    if 'usuario_id' not in session:
        return jsonify({'error': 'No autorizado'}), 401
    
    data = request.get_json()
    nombre = data.get('nombre')
    email = data.get('email')
    usuario = data.get('usuario')
    
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(
                "UPDATE usuarios SET nombre = %s, email = %s, usuario = %s WHERE id = %s",
                (nombre, email, usuario, session['usuario_id'])
            )
            conn.commit()
            session['usuario_nombre'] = nombre
            session['usuario_email'] = email
            return jsonify({'success': True})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})
    finally:
        conn.close()

@app.route('/api/perfil/password', methods=['POST'])
def api_cambiar_password():
    if 'usuario_id' not in session:
        return jsonify({'error': 'No autorizado'}), 401
    
    data = request.get_json()
    password_nueva = data.get('password_nueva')
    
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            password_hash = hashlib.md5(password_nueva.encode()).hexdigest()
            cursor.execute(
                "UPDATE usuarios SET password = %s WHERE id = %s",
                (password_hash, session['usuario_id'])
            )
            conn.commit()
            return jsonify({'success': True})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})
    finally:
        conn.close()

# ============================================
# API - MERCADO PAGO
# ============================================

@app.route('/api/mercadopago/preferencia', methods=['POST'])
def api_mp_preferencia():
    if 'usuario_id' not in session:
        return jsonify({'error': 'No autorizado'}), 401
    
    data = request.get_json()
    producto_id = data.get('producto_id')
    
    conn = get_db_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT * FROM productos WHERE id = %s", (producto_id,))
            producto = cursor.fetchone()
            
            if not producto:
                return jsonify({'error': 'Producto no encontrado'}), 404
            
            import requests
            
            base_url = get_base_url()
            session['producto_id_pendiente'] = producto_id
            
            preference_data = {
                'items': [{
                    'id': str(producto_id),
                    'title': producto['nombre'][:256],
                    'description': (producto['descripcion'] or '')[:256],
                    'quantity': 1,
                    'currency_id': 'MXN',
                    'unit_price': float(producto['precio'])
                }],
                'payer': {
                    'name': session.get('usuario_nombre', ''),
                    'email': session.get('usuario_email', '')
                },
                'external_reference': f"PROD_{producto_id}_USER_{session['usuario_id']}_{int(datetime.now().timestamp())}"
            }
            
            # SOLO agregar back_urls si tenemos URL publica
            if TIENE_URL_PUBLICA:
                preference_data['back_urls'] = {
                    'success': base_url + '/exito?metodo=mercadopago',
                    'failure': base_url + '/cancelado',
                    'pending': base_url + '/exito?metodo=mercadopago&pending=1'
                }
                preference_data['auto_return'] = 'approved'
                print(f"✅ back_urls activado: {base_url}")
            else:
                print(f"⚠️ Sin URL publica. Modo sandbox manual.")
            
            headers = {
                'Authorization': 'Bearer APP_USR-7667354229771081-050518-4cb1dfab84659acb9c549be0e84213a3-2132422632',
                'Content-Type': 'application/json'
            }
            
            response = requests.post(
                'https://api.mercadopago.com/checkout/preferences',
                json=preference_data,
                headers=headers,
                timeout=30
            )
            
            mp_data = response.json()
            print(f"MP Status: {response.status_code}")
            
            if response.status_code in [200, 201]:
                return jsonify({
                    'init_point': mp_data.get('init_point'),
                    'sandbox_init_point': mp_data.get('sandbox_init_point'),
                    'preference_id': mp_data.get('id')
                })
            else:
                print(f"❌ MP Error: {mp_data}")
                return jsonify({
                    'error': 'Error de Mercado Pago',
                    'message': mp_data.get('message', 'Error desconocido'),
                    'status': response.status_code
                }), 400
                
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        conn.close()

# ============================================
# INICIAR
# ============================================

if __name__ == '__main__':
    # Crear producto de prueba
    try:
        conn = get_db_connection()
        with conn.cursor() as cursor:
            cursor.execute("SELECT id FROM productos WHERE precio = 1.00 LIMIT 1")
            if not cursor.fetchone():
                cursor.execute(
                    "INSERT INTO productos (nombre, descripcion, precio) VALUES (%s, %s, %s)",
                    ('Sticker Oficial Tienda Digital', 'Sticker digital de prueba. Envio inmediato.', 1.00)
                )
                conn.commit()
                print("✅ Producto $1.00 creado")
        conn.close()
    except Exception as e:
        print(f"⚠️ Error: {e}")
    
    port = int(os.environ.get('PORT', 5000))
    print(f"🚀 Iniciando en puerto {port}")
    print(f"📌 BASE_URL: {BASE_URL or 'NO CONFIGURADA'}")
    print(f"📌 TIENE_URL_PUBLICA: {TIENE_URL_PUBLICA}")
    
    app.run(debug=False, host='0.0.0.0', port=port)
