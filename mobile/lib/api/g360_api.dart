import 'dart:convert';
import 'dart:io';
import 'dart:math';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class G360ApiException implements Exception {
  G360ApiException(this.message);
  final String message;

  @override
  String toString() => message;
}

class G360Api {
  G360Api({this.baseUrl = const String.fromEnvironment('API_URL', defaultValue: 'http://10.0.2.2:8000/api/v1')});

  final String baseUrl;
  final HttpClient _client = HttpClient();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  String? token;

  Future<void> login(String email, String password) async {
    final deviceIdentifier = await _stableDeviceIdentifier();
    final data = await _request('POST', '/auth/login', body: {
      'email': email,
      'password': password,
      'dispositivo': {
        'identificador': deviceIdentifier,
        'plataforma': 'android',
        'modelo_dispositivo': 'flutter-r6',
        'versao_sistema': Platform.operatingSystemVersion,
        'versao_app': '1.0.0',
      },
    });
    token = data['token'] as String;
    await _storage.write(key: 'g360_api_token', value: token);
  }

  Future<List<Map<String, dynamic>>> applications() async {
    final data = await _request('GET', '/aplicacoes') as Map<String, dynamic>;
    return (data['items'] as List).cast<Map<String, dynamic>>();
  }

  Future<List<Map<String, dynamic>>> students(String applicationId) async {
    final data = await _request('GET', '/aplicacoes/$applicationId/alunos') as List;
    return data.cast<Map<String, dynamic>>();
  }

  Future<Map<String, dynamic>> dashboard(String applicationId) async {
    return (await _request('GET', '/aplicacoes/$applicationId/dashboard')) as Map<String, dynamic>;
  }

  Future<dynamic> _request(String method, String path, {Map<String, dynamic>? body}) async {
    final request = await _client.openUrl(method, Uri.parse('$baseUrl$path'));
    request.headers.contentType = ContentType.json;
    request.headers.set(HttpHeaders.acceptHeader, ContentType.json.mimeType);
    if (token != null) request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
    if (body != null) request.write(jsonEncode(body));

    final response = await request.close();
    final raw = await utf8.decoder.bind(response).join();
    final decoded = jsonDecode(raw) as Map<String, dynamic>;
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw G360ApiException((decoded['error']?['message'] as String?) ?? 'Nao foi possivel concluir a operacao.');
    }

    return decoded['data'];
  }

  static String _uuid() {
    final random = Random.secure();
    final bytes = List<int>.generate(16, (_) => random.nextInt(256));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    final value = bytes.map((byte) => byte.toRadixString(16).padLeft(2, '0')).join();
    return '${value.substring(0, 8)}-${value.substring(8, 12)}-${value.substring(12, 16)}-${value.substring(16, 20)}-${value.substring(20)}';
  }

  Future<String> _stableDeviceIdentifier() async {
    final stored = await _storage.read(key: 'g360_device_identifier');
    if (stored != null && stored.isNotEmpty) return stored;

    final identifier = _uuid();
    await _storage.write(key: 'g360_device_identifier', value: identifier);
    return identifier;
  }
}
