<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiClient
{
    protected string $baseUrl;
    protected string $email;
    protected string $password;
    protected ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = env('API_URL');
        $this->email = env('API_USER');
        $this->password = env('API_PASSWORD');
    }

    // Autenticación para obtener token
    public function authenticate(): bool
    {
        if ($this->token) {
            return true;
        }

        $response = Http::post("{$this->baseUrl}/token", [
            'email' => $this->email,
            'password' => $this->password,
        ]);

        if ($response->successful()) {
            $this->token = $response->json('token');
            return true;
        }

        return false;
    }

    /**
     * Llama a la API con método dinámico, ruta variable y datos opcionales.
     *
     * @param string $method  'get', 'post', 'put', 'delete', etc
     * @param string $endpoint  Ejemplo: 'user', 'employee/list', etc
     * @param array $data  Datos para enviar en body (POST, PUT, DELETE)
     * @return array  Respuesta JSON decodificada
     * @throws \Exception
     */
    public function callApi(string $method, string $endpoint, array $data = []): array
    {
        if (!$this->authenticate()) {
            throw new \Exception('No se pudo autenticar con la API');
        }

        $method = strtolower($method);
        $url = "{$this->baseUrl}/{$endpoint}";

        // Preparar la petición con token
        $request = Http::withToken($this->token);

        // Ejecutar según método
        switch ($method) {
            case 'get':
                $response = $request->get($url, $data); // $data se usa como query params
                break;

            case 'post':
                $response = $request->post($url, $data);
                break;

            case 'put':
                $response = $request->put($url, $data);
                break;

            case 'delete':
                $response = $request->delete($url, $data);
                break;

            default:
                throw new \Exception("Método HTTP no soportado: {$method}");
        }

        if ($response->successful()) {
            return $response->json();
        }

        $message = $response->json('message') ?? 'Error en la petición a API';
        throw new \Exception($message);
    }

    // Revocar token para seguridad
    public function revokeToken(): void
    {
        if (!$this->token) {
            return;
        }

        Http::withToken($this->token)->post("{$this->baseUrl}/token/revoke");
        $this->token = null;
    }
}
