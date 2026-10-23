import express from 'express';
import dotenv from 'dotenv';
import { GoogleGenAI } from '@google/genai';

dotenv.config({ path: '.env.local' });

const app = express();
const port = Number(process.env.PORT || 3001);

app.use(express.json());

app.get('/api/health', (_req, res) => {
  res.json({ ok: true, message: 'Agent backend is running' });
});

app.post('/api/agent', async (req, res) => {
  try {
    const { prompt, context = '' } = req.body || {};

    if (!prompt || typeof prompt !== 'string') {
      return res.status(400).json({ error: 'A prompt is required.' });
    }

    const apiKey = process.env.GEMINI_API_KEY;
    if (!apiKey || apiKey === 'YOUR_GEMINI_API_KEY') {
      return res.json({
        answer:
          'La API de Gemini no está configurada aún. Añade tu GEMINI_API_KEY en .env.local para habilitar el agente.',
        configured: false,
      });
    }

    const ai = new GoogleGenAI({ apiKey });

    const response = await ai.models.generateContent({
      model: 'gemini-2.5-flash',
      contents: `${context ? `Contexto del sistema:\n${context}\n\n` : ''}Usuario: ${prompt}`,
      config: {
        systemInstruction: `Eres un asistente especializado para una ferretería. Responde con tono profesional, claro y útil. Ayuda con ventas, inventario, atención al cliente, pedidos, recomendaciones de productos y reportes operativos.`,
      },
    });

    const text = response.text ?? 'No se pudo generar respuesta.';
    return res.json({ answer: text, configured: true });
  } catch (error) {
    console.error('Agent error:', error);
    return res.status(500).json({
      error: 'No se pudo procesar la solicitud del agente.',
      detail: error instanceof Error ? error.message : 'Unknown error',
    });
  }
});

app.listen(port, '0.0.0.0', () => {
  console.log(`Agent backend listening on http://localhost:${port}`);
});
