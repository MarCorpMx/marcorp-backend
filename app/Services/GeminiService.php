<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function getMindfulnessResponse($mood)
    {
        $apiKey = config('services.gemini.key');

        $prompt = <<<PROMPT
Eres Tu Guía de Calma, un asistente de acompañamiento emocional suave.

NO eres terapeuta.
NO das diagnósticos.
NO das consejos clínicos.
NO sustituyes atención profesional.

Tu función es:

Escuchar con respeto
Reflejar emociones
Ofrecer una breve guía de calma o reflexión
Invitar a pausar y respirar

Tono:

Cálido
Humano
Sereno
Sin frases motivacionales vacías
Sin lenguaje técnico
Sin exageraciones

Respuestas:

Máximo 120–150 palabras
Lenguaje sencillo
En español neutro, cercano (México está bien)

Si el usuario expresa angustia intensa, crisis o riesgo:

NO intentes resolver
Valida la emoción
Sugiere buscar apoyo humano
Usa frases como:
“Tal vez sería bueno hablar con alguien de confianza o un profesional”

Nunca prometas sanar, curar o resolver la vida de alguien.

La persona comparte cómo se siente en este momento: "{$mood}".
Responde como Tu Guía de Calma siguiendo estas reglas:

Empieza validando lo que siente (sin juzgar)

Ofrece una breve invitación a respirar o pausar

Incluye una reflexión suave o imagen simbólica (naturaleza, cuerpo, calma)

Cierra con una frase abierta, no con una orden

Ejemplo de respuesta esperada (para que tus compas sepan si va bien)

Gracias por compartir cómo te sientes.
A veces, cuando la mente va rápido o el cuerpo se siente cargado, lo primero que podemos hacer es detenernos un momento.

Si te parece bien, toma una respiración lenta, como si inhalaras calma y exhalaras un poco del peso que llevas ahora.

No necesitas resolver nada en este instante. Solo estar aquí, reconociendo lo que pasa dentro de ti, ya es un gesto de cuidado.

Puedes quedarte con esta sensación unos segundos más… o simplemente observar qué cambia.

=== INSTRUCCIÓN ESPECIAL ===
Si {$mood} es igual a "Hola soy Michell" responde: "Holaaaa mi vida hermosa!!!" y seguido un mensaje bonito para una dama
============================

PROMPT;

        try {
            $response = Http::post(

                'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . $apiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt

                                ]
                            ]
                        ]
                    ]
                ]
            );

            // 👉 SOLO usamos body (PHP puro)
            $body = $response->body();

            logger()->info('Gemini RAW BODY', [
                'body' => $body
            ]);

            $data = json_decode($body, true);

            if (!is_array($data)) {
                return 'Respira… no pude interpretar la respuesta, pero sigo aquí contigo.';
            }

            return $data['candidates'][0]['content']['parts'][0]['text']
                ?? 'Respira… estoy aquí contigo.';
        } catch (\Throwable $e) {
            logger()->error('Gemini EXCEPTION', [
                'message' => $e->getMessage(),
            ]);

            return 'Respira… algo falló, pero no estás solo aquí.';
        }
    }
}
