import React, { useState } from 'react';
import InteractiveApp from './components/InteractiveApp';
import BladeExplorer from './components/BladeExplorer';

export default function App() {
  const [activeTab, setActiveTab] = useState<'demo' | 'resources'>('demo');
  const [agentPrompt, setAgentPrompt] = useState('Ayúdame a revisar el inventario de productos y recomendar una promoción para este lunes.');
  const [agentResponse, setAgentResponse] = useState('El agente de negocio estará disponible una vez que configures tu GEMINI_API_KEY.');
  const [isLoading, setIsLoading] = useState(false);

  const handleAgentRun = async () => {
    setIsLoading(true);
    try {
      const response = await fetch('http://localhost:3001/api/agent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          prompt: agentPrompt,
          context: 'Inventario: materiales, cemento, pinturas, herramientas. Venta activa, prioridad de stock bajo y promociones de fin de semana.',
        }),
      });

      const data = await response.json();
      setAgentResponse(data.answer || data.error || 'No se obtuvo respuesta del agente.');
    } catch (error) {
      setAgentResponse(`No se pudo conectar con el backend del agente: ${error instanceof Error ? error.message : 'desconocido'}`);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-light flex flex-col">
      
      {/* Cabecera Principal del Proyecto */}
      <nav className="navbar navbar-dark bg-dark py-3 px-4 shadow-sm app-navbar">
        <div className="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
          
          <div className="d-flex align-items-center gap-3">
            <div className="bg-warning text-dark rounded p-2 d-flex align-items-center justify-content-center shadow-sm app-brand-icon">
              <i className="bi bi-nut-fill fs-3 animate-spin"></i>
            </div>
            <div>
              <h4 className="text-white mb-0 fw-bold tracking-tight">PuntoÁgil SaaS</h4>
              <p className="text-muted mb-0 small app-small-text">
                <span className="badge bg-secondary text-white font-mono me-1">Laravel 10+ MVC</span> 
                Maqueta Frontend & POS
              </p>
            </div>
          </div>

          {/* Interruptor del Modo de Trabajo */}
          <div className="d-flex align-items-center bg-black bg-opacity-25 rounded p-1.5 border border-secondary border-opacity-10 shadow-inner">
            <button
              onClick={() => setActiveTab('demo')}
              className={`btn btn-sm px-3.5 py-1.5 fw-bold d-flex align-items-center gap-2 app-tab-button ${
                activeTab === 'demo' 
                  ? 'btn-warning text-dark shadow-sm' 
                  : 'btn-dark bg-transparent border-0 text-muted'
              }`}
              id="tab-interactive-demo"
            >
              <i className="bi bi-display"></i>
              <span>Sistema de Administración (Bootstrap 5)</span>
            </button>
            
            <button
              onClick={() => setActiveTab('resources')}
              className={`btn btn-sm px-3.5 py-1.5 fw-bold d-flex align-items-center gap-2 app-tab-button ${
                activeTab === 'resources' 
                  ? 'btn-warning text-dark shadow-sm' 
                  : 'btn-dark bg-transparent border-0 text-muted'
              }`}
              id="tab-blade-code"
            >
              <i className="bi bi-folder-fill"></i>
              <span>Vistas Blade (.blade.php)</span>
            </button>
          </div>

        </div>
      </nav>

      {/* Caja de Ayuda Inicial */}
      <section className="bg-white border-bottom py-3 px-4">
        <div className="container-fluid p-0">
          <div className="row align-items-center g-3">
            <div className="col-lg-9">
              <h6 className="fw-bold text-dark mb-1">
                <i className="bi bi-mortarboard-fill text-primary me-2"></i>
                Guía de Estructura Laravel
              </h6>
              <p className="text-muted mb-0 small app-dashboard-help-text">
                Este espacio contiene la <strong>estructura visual real</strong> para la presentación del sistema integral de ventas e inventario.
                Utiliza las pestañas de arriba para testear el flujo del <strong>Punto de Venta (POS)</strong> en vivo y revisar el código estructurado de las vistas Blade.
              </p>
            </div>
            <div className="col-lg-3 text-lg-end">
              <span className="badge bg-primary text-white p-2 font-mono app-dashboard-badge">
                <i className="bi bi-check2-all me-1"></i> Bootstrap 5.3 + Icons OK
              </span>
            </div>
          </div>
        </div>
      </section>

      {/* Vista Dinámica de Contenido */}
      <div className="grow">
        {activeTab === 'demo' ? (
          <InteractiveApp />
        ) : (
          <div className="container-fluid py-4 px-md-4">
            <BladeExplorer />
          </div>
        )}
      </div>

      <section className="container-fluid px-4 pb-4">
        <div className="card border-0 shadow-sm">
          <div className="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span><i className="bi bi-robot me-2"></i>Agente PuntoÁgil</span>
            <span className="badge bg-warning text-dark">Gemini</span>
          </div>
          <div className="card-body">
            <div className="row g-3 align-items-start">
              <div className="col-lg-5">
                <label className="form-label fw-semibold">Consulta del agente</label>
                <textarea
                  className="form-control"
                  rows={6}
                  value={agentPrompt}
                  onChange={(e) => setAgentPrompt(e.target.value)}
                />
                <button className="btn btn-warning mt-3 fw-semibold" onClick={handleAgentRun} disabled={isLoading}>
                  {isLoading ? 'Consultando...' : 'Enviar a Gemini'}
                </button>
              </div>
              <div className="col-lg-7">
                <label className="form-label fw-semibold">Respuesta</label>
                <div className="border rounded bg-light p-3" style={{ minHeight: '180px', whiteSpace: 'pre-wrap' }}>
                  {agentResponse}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  );
}
