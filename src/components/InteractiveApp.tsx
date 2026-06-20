import React, { useState, useMemo } from 'react';
import { Product, CartItem, SaleTransaction, Role, Screen, User } from '../types';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';

const INITIAL_USERS: User[] = [
  { id: 1, name: 'Yahir Cuess', email: 'yahircuess7@gmail.com', role: 'admin', status: 'active', createdAt: '01/01/2026' },
  { id: 2, name: 'Aníbal Tornillo', email: 'admin@ferreteria.com', role: 'admin', status: 'active', createdAt: '15/01/2024' },
  { id: 3, name: 'Carlos Gómez', email: 'cajero@ferreteria.com', role: 'cajero', status: 'active', createdAt: '10/05/2026' },
  { id: 4, name: 'María Velez', email: 'maria.velez@ferreteria.com', role: 'cajero', status: 'inactive', createdAt: '22/02/2025' }
];

// Datos Iniciales de la Ferretería
const INITIAL_PRODUCTS: Product[] = [
  {
    id: 1,
    sku: 'SKU-TORN-009',
    name: 'Tornillo Drywall 1x6 pulgadas (100un)',
    description: 'Para fijación de paneles de yeso a pernos de madera suave o perfiles de acero ligero.',
    price: 4.50,
    stock: 140,
    category: 'Fijaciones y Tornillos',
    unit: 'Cajas',
    brand: 'StanTools'
  },
  {
    id: 2,
    sku: 'SKU-CEME-512',
    name: 'Cemento Gris Portland Especial 50Kg',
    description: 'Cemento hidráulico para la construcción de losas de alta resistencia y mortero estructural. Marca Holcim.',
    price: 9.20,
    stock: 8,
    category: 'Construcción y Áridos',
    unit: 'Bolsas',
    brand: 'Holcim'
  },
  {
    id: 3,
    sku: 'SKU-ELEC-401',
    name: 'Martillo Demoledor Eléctrico Bosch 1100W',
    description: 'Martillo potente con encastre SDS Max, 11 Joules de fuerza de impacto. Hecho en Alemania.',
    price: 345.00,
    stock: 0, // REGULA VISUAL: STOCK 0 muestra "Sin Stock" en rojo
    category: 'Herramientas Eléctricas',
    unit: 'Unidades',
    brand: 'Bosch'
  },
  {
    id: 4,
    sku: 'SKU-ALAM-103',
    name: 'Rollo de Alambre Recocido Calibre N.18 (5Kg)',
    description: 'Alambre negro recocido de gran flexibilidad y resistencia para amarras de estribos y estructuras de fierro.',
    price: 2.10,
    stock: 75,
    category: 'Fijaciones y Tornillos',
    unit: 'Rollos',
    brand: 'AceroAndino'
  },
  {
    id: 5,
    sku: 'SKU-MANU-882',
    name: 'Alicate Universal Profesional Stanley 8"',
    description: 'Alicate multiuso de acero forjado con tratamiento térmico y mango bi-material antideslizante.',
    price: 12.50,
    stock: 32,
    category: 'Herramientas Manuales',
    unit: 'Unidades',
    brand: 'Stanley'
  },
  {
    id: 6,
    sku: 'SKU-DISC-021',
    name: 'Disco de Corte Diamante Ultra Delgado 4.5"',
    description: 'Disco flap de circonio grano 80 para esmeriles, pulido y desbaste ultra rápido en acero y metales duros.',
    price: 3.15,
    stock: 120,
    category: 'Herramientas Manuales',
    unit: 'Unidades',
    brand: 'DeWalt'
  },
  {
    id: 7,
    sku: 'SKU-PLOM-221',
    name: 'Tubo de Presión PVC para Agua 1/2" x 6m',
    description: 'Tubo de PVC rígido para la distribución doméstica de agua potable fría a alta presión.',
    price: 3.80,
    stock: 3,
    category: 'Plomería',
    unit: 'Unidades',
    brand: 'Plastigama'
  },
  {
    id: 8,
    sku: 'SKU-PINT-983',
    name: 'Pintura Látex Supremo Blanco (Balde 5Gls)',
    description: 'Pintura acrílica emulsionada de alta lavabilidad y excelente poder cubriente para fachadas e interiores.',
    price: 48.90,
    stock: 15,
    category: 'Pinturas',
    unit: 'Cajas',
    brand: 'Sherwin-Williams'
  }
];

export default function InteractiveApp() {
  // Estados de navegación e identidad
  const [screen, setScreen] = useState<Screen>('home');
  const [role, setRole] = useState<Role>('admin');
  const [isLoggedIn, setIsLoggedIn] = useState<boolean>(false);
  const [activeUser, setActiveUser] = useState<string>('Yahir Cuess (Admin)');

  const [users, setUsers] = useState<User[]>(INITIAL_USERS);

  // Base de datos de productos interactivos en State para simular CRUD
  const [products, setProducts] = useState<Product[]>(INITIAL_PRODUCTS);
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

  // Estados del Carrito de Ventas en POS
  const [cart, setCart] = useState<CartItem[]>([]);
  const [customerDni, setCustomerDni] = useState<string>('0932184912');
  const [customerName, setCustomerName] = useState<string>('Consumidor Final');
  const [paymentMethod, setPaymentMethod] = useState<'efectivo' | 'tarjeta' | 'transferencia'>('efectivo');

  // Estado del Facturador Térmico / Modal de Recibo
  const [invoice, setInvoice] = useState<SaleTransaction | null>(null);
  const [showReceipt, setShowReceipt] = useState<boolean>(false);

  // Filtros del módulo de inventario
  const [inventorySearch, setInventorySearch] = useState<string>('');
  const [inventoryCategory, setInventoryCategory] = useState<string>('');
  const [inventoryStockFilter, setInventoryStockFilter] = useState<string>(''); // 'todos', 'bajo', 'agotado'

  // Filtros del POS
  const [posSearch, setPosSearch] = useState<string>('');
  const [posCategory, setPosCategory] = useState<string>('');

  // Formulario de Producto (Crear / Editar)
  const [formSku, setFormSku] = useState<string>('');
  const [formName, setFormName] = useState<string>('');
  const [formCategory, setFormCategory] = useState<string>('Fijaciones y Tornillos');
  const [formPrice, setFormPrice] = useState<number>(0);
  const [formStock, setFormStock] = useState<number>(0);
  const [formUnit, setFormUnit] = useState<string>('Unidades');
  const [formBrand, setFormBrand] = useState<string>('');
  const [formDescription, setFormDescription] = useState<string>('');
  const [formError, setFormError] = useState<string>('');

  // Login inputs mockup
  const [loginEmail, setLoginEmail] = useState<string>('admin@ferreteria.com');
  const [loginPassword, setLoginPassword] = useState<string>('123456');
  const [loginError, setLoginError] = useState<string>('');

  // Historial de ventas de la sesión para mostrar en el Dashboard
  const [transactions, setTransactions] = useState<SaleTransaction[]>([
    {
      id: 'TX-1001',
      invoiceNumber: 'FAC-2026-001',
      date: '11/06/2026 - 13:45',
      items: [
        { product: INITIAL_PRODUCTS[1], quantity: 2 },
        { product: INITIAL_PRODUCTS[0], quantity: 3 }
      ],
      subtotal: 31.90,
      tax: 3.83,
      total: 35.73,
      cashier: 'Carlos Gómez',
      customerName: 'Juan Pérez Meléndez',
      customerDni: '0918237123',
      paymentMethod: 'efectivo'
    },
    {
      id: 'TX-1002',
      invoiceNumber: 'FAC-2026-002',
      date: '11/06/2026 - 12:20',
      items: [
        { product: INITIAL_PRODUCTS[4], quantity: 1 },
        { product: INITIAL_PRODUCTS[7], quantity: 5 }
      ],
      subtotal: 257.00,
      tax: 30.84,
      total: 287.84,
      cashier: 'Yahir Cuess (Admin)',
      customerName: 'Constructora Alpha S.A.',
      customerDni: '1792384710001',
      paymentMethod: 'transferencia'
    }
  ]);

  // Manejador de Login
  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    if (!loginEmail || !loginPassword) {
      setLoginError('Por favor complete todos los campos.');
      return;
    }

    if (loginEmail === 'admin@ferreteria.com' && loginPassword === '123456') {
      setRole('admin');
      setActiveUser('Aníbal Tornillo (Admin)');
      setIsLoggedIn(true);
      setScreen('dashboard');
      setLoginError('');
    } else if (loginEmail === 'cajero@ferreteria.com' && loginPassword === '123456') {
      setRole('cajero');
      setActiveUser('Carlos Gómez (Cajero)');
      setIsLoggedIn(true);
      setScreen('dashboard');
      setLoginError('');
    } else {
      setLoginError('Credenciales incorrectas. Pruebe con los botones de acceso rápido.');
    }
  };

  const handleQuickLogin = (selectedRole: Role) => {
    if (selectedRole === 'admin') {
      setLoginEmail('admin@ferreteria.com');
      setLoginPassword('123456');
      setRole('admin');
      setActiveUser('Aníbal Tornillo (Admin)');
      setIsLoggedIn(true);
      setScreen('dashboard');
      setLoginError('');
    } else {
      setLoginEmail('cajero@ferreteria.com');
      setLoginPassword('123456');
      setRole('cajero');
      setActiveUser('Carlos Gómez (Cajero)');
      setIsLoggedIn(true);
      setScreen('dashboard');
      setLoginError('');
    }
  };

  // Logout
  const handleLogout = () => {
    setIsLoggedIn(false);
    setScreen('home');
    setCart([]);
  };

  // Cálculo de estadísticas globales para el Dashboard
  const stats = useMemo(() => {
    const totalProducts = products.length;
    const outOfStockCount = products.filter(p => p.stock === 0).length;
    const lowStockCount = products.filter(p => p.stock > 0 && p.stock <= 8).length;
    
    // Suma de ventas del día
    const salesTotal = transactions.reduce((curr, t) => curr + t.total, 0);
    const ordersCount = transactions.length;

    return {
      totalProducts,
      outOfStockCount,
      lowStockCount,
      salesTotal,
      ordersCount
    };
  }, [products, transactions]);

  // Filtrado de Inventario en tiempo real
  const filteredProducts = useMemo(() => {
    return products.filter(p => {
      const matchesSearch = p.name.toLowerCase().includes(inventorySearch.toLowerCase()) || 
                            p.sku.toLowerCase().includes(inventorySearch.toLowerCase()) || 
                            p.category.toLowerCase().includes(inventorySearch.toLowerCase());
      
      const matchesCategory = inventoryCategory === '' || p.category === inventoryCategory;
      
      let matchesStock = true;
      if (inventoryStockFilter === 'bajo') {
        matchesStock = p.stock > 0 && p.stock <= 8;
      } else if (inventoryStockFilter === 'sin_stock') {
        matchesStock = p.stock === 0;
      } else if (inventoryStockFilter === 'disponible') {
        matchesStock = p.stock > 8;
      }

      return matchesSearch && matchesCategory && matchesStock;
    });
  }, [products, inventorySearch, inventoryCategory, inventoryStockFilter]);

  // Filtrado del Catálogo POS para el Cajero
  const posFilteredProducts = useMemo(() => {
    return products.filter(p => {
      const matchesSearch = p.name.toLowerCase().includes(posSearch.toLowerCase()) || 
                            p.sku.toLowerCase().includes(posSearch.toLowerCase());
      const matchesCategory = posCategory === '' || p.category === posCategory;
      return matchesSearch && matchesCategory;
    });
  }, [products, posSearch, posCategory]);

  // Editar Producto - Cargar en formulario
  const handleEditClick = (p: Product) => {
    setSelectedProduct(p);
    setFormSku(p.sku);
    setFormName(p.name);
    setFormCategory(p.category);
    setFormPrice(p.price);
    setFormStock(p.stock);
    setFormUnit(p.unit);
    setFormBrand(p.brand || '');
    setFormDescription(p.description);
    setFormError('');
    setScreen('inventory_form');
  };

  // Registrar Producto Nuevo - Resetear formulario
  const handleAddNewClick = () => {
    setSelectedProduct(null);
    setFormSku(`SKU-GEN-${Math.floor(100 + Math.random() * 900)}`);
    setFormName('');
    setFormCategory('Fijaciones y Tornillos');
    setFormPrice(0.50);
    setFormStock(50);
    setFormUnit('Unidades');
    setFormBrand('');
    setFormDescription('');
    setFormError('');
    setScreen('inventory_form');
  };

  // Guardar Formulario (Crear o Actualizar)
  const handleSaveProduct = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formSku || !formName || formPrice <= 0 || formStock < 0) {
      setFormError('Por favor complete todos los campos obligatorios con valores correctos.');
      return;
    }

    if (selectedProduct) {
      // Editar
      setProducts(prev => prev.map(p => p.id === selectedProduct.id ? {
        ...p,
        sku: formSku,
        name: formName,
        category: formCategory,
        price: Number(formPrice),
        stock: Number(formStock),
        unit: formUnit,
        brand: formBrand,
        description: formDescription
      } : p));
    } else {
      // Crear
      const newProduct: Product = {
        id: Date.now(),
        sku: formSku,
        name: formName,
        category: formCategory,
        price: Number(formPrice),
        stock: Number(formStock),
        unit: formUnit,
        brand: formBrand,
        description: formDescription
      };
      setProducts(prev => [newProduct, ...prev]);
    }

    setScreen('inventory');
  };

  // Eliminar Producto Simulado
  const handleDeleteProduct = (id: number, sku: string) => {
    if (confirm(`¿Está seguro de que desea eliminar el producto ${sku} del inventario?`)) {
      setProducts(prev => prev.filter(p => p.id !== id));
    }
  };

  // Lógica del Carrito del Punto de Venta (POS)
  const handleAddToCart = (product: Product) => {
    if (product.stock === 0) {
      alert('¡Error! No hay existencias disponibles del item seleccionado.');
      return;
    }

    setCart(prev => {
      const existing = prev.find(item => item.product.id === product.id);
      if (existing) {
        if (existing.quantity >= product.stock) {
          alert(`Límite alcanzado. Solo hay ${product.stock} unidades de este producto en el stock de bodega.`);
          return prev;
        }
        return prev.map(item => item.product.id === product.id 
          ? { ...item, quantity: item.quantity + 1 } 
          : item
        );
      } else {
        return [...prev, { product, quantity: 1 }];
      }
    });
  };

  const updateCartQuantity = (id: number, val: number, maxStock: number) => {
    if (val <= 0) {
      setCart(prev => prev.filter(item => item.product.id !== id));
      return;
    }
    if (val > maxStock) {
      alert(`Sólo existen ${maxStock} unidades en el stock físico.`);
      return;
    }
    setCart(prev => prev.map(item => item.product.id === id 
      ? { ...item, quantity: val } 
      : item
    ));
  };

  const removeFromCart = (id: number) => {
    setCart(prev => prev.filter(item => item.product.id !== id));
  };

  // Cálculos del Carrito
  const cartSubtotal = useMemo(() => {
    return cart.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
  }, [cart]);

  const cartTax = useMemo(() => {
    return cartSubtotal * 0.12; // IVA 12% para Ecuador y otros países de LatAm
  }, [cartSubtotal]);

  const cartTotal = useMemo(() => {
    return cartSubtotal + cartTax;
  }, [cartSubtotal, cartTax]);

  // Completar Venta POS y Reducir Stock
  const handleFinalizeSale = () => {
    if (cart.length === 0) {
      alert('El carrito de compras está vacío.');
      return;
    }

    const nextInvoiceNumber = `FAC-2026-${String(transactions.length + 34).padStart(3, '0')}`;
    const newTx: SaleTransaction = {
      id: `TX-${Date.now()}`,
      invoiceNumber: nextInvoiceNumber,
      date: new Date().toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      }) + ' - ' + new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
      items: [...cart],
      subtotal: Number(cartSubtotal.toFixed(2)),
      tax: Number(cartTax.toFixed(2)),
      total: Number(cartTotal.toFixed(2)),
      cashier: activeUser,
      customerName: customerName || 'Consumidor Final',
      customerDni: customerDni || '9999999999',
      paymentMethod: paymentMethod
    };

    // Registrar transacción
    setTransactions(prev => [newTx, ...prev]);

    // Reducir stock
    setProducts(prev => prev.map(p => {
      const boughtItem = cart.find(c => c.product.id === p.id);
      if (boughtItem) {
        return {
          ...p,
          stock: Math.max(0, p.stock - boughtItem.quantity)
        };
      }
      return p;
    }));

    // Cargar para recibo
    setInvoice(newTx);
    setShowReceipt(true);

    // Limpiar carrito
    setCart([]);
    setCustomerName('Consumidor Final');
    setCustomerDni('0932184912');
  };

  return (
    <div className="bg-light min-h-100 flex flex-column">
      
      {/* Barra de Pruebas */}
      <div className="bg-warning text-dark px-3 py-2 d-flex flex-wrap align-items-center justify-content-between border-bottom border-warning border-2 gap-3" style={{ fontSize: '0.85rem' }}>
        <p className="mb-0 fw-semibold d-flex align-items-center gap-2">
          <i className="bi bi-mortarboard-fill fs-5 text-dark"></i>
          <span><strong>MODO INTERACTIVO:</strong> Estás probando el funcionamiento administrativo.</span>
        </p>
        <div className="d-flex align-items-center gap-2">
          <span className="text-secondary-dark fw-bold">Rol Operativo Actual:</span>
          <div className="btn-group btn-group-sm rounded shadow-sm">
            <button 
              className={`btn ${role === 'admin' ? 'btn-primary fw-bold' : 'btn-light border text-dark'}`}
              onClick={() => handleQuickLogin('admin')}
              id="role-admin-toggle"
            >
              <i className="bi bi-person-badge me-1"></i> Administrador
            </button>
            <button 
              className={`btn ${role === 'cajero' ? 'btn-success fw-bold' : 'btn-light border text-dark'}`}
              onClick={() => handleQuickLogin('cajero')}
              id="role-cajero-toggle"
            >
              <i className="bi bi-cash-coin me-1"></i> Cajero de POS
            </button>
          </div>
        </div>
      </div>

      {/* ---------------- PÁGINA: LANDING / HOME ---------------- */}
      {screen === 'home' && (
        <div>
          {/* Header */}
          <nav className="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
            <div className="container">
              <a className="navbar-brand text-primary fw-bold d-flex align-items-center gap-2" href="#" onClick={(e) => { e.preventDefault(); setScreen('home'); }}>
                <i className="bi bi-nut-fill text-warning fs-3"></i>
                <span className="fw-extrabold tracking-tight" style={{ letterSpacing: '-0.3px' }}>FERRETERÍA EL TORNILLO</span>
              </a>
              <div className="ms-auto">
                <button 
                  onClick={() => handleQuickLogin(role)}
                  className="btn btn-outline-primary fw-bold px-4 py-2" 
                  style={{ borderRadius: '8px' }}
                  id="landing-enter-system"
                >
                  <i className="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                </button>
              </div>
            </div>
          </nav>

          {/* Hero Banner */}
          <header className="py-5 text-white" style={{ background: 'linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%)', borderBottom: '6px solid #f97316' }}>
            <div className="container py-5">
              <div className="row align-items-center g-5">
                <div className="col-lg-7 text-center text-lg-start">
                  <span className="badge bg-warning text-dark px-3 py-2 fs-7 fw-bold mb-3 rounded-pill text-uppercase">EXCELENCIA EN CONSTRUCCIÓN</span>
                  <h1 className="display-4 fw-extrabold text-white mb-3" style={{ fontWeight: 800, letterSpacing: '-1px' }}>
                    Herramientas y pernos que <span style={{ color: '#f97316' }}>sostienen</span> tus obras
                  </h1>
                  <p className="lead text-light opacity-90 fs-5 mb-4" style={{ maxWidth: '620px' }}>
                    Líderes locales en clavería, cementos Portland de alta duración, maquinaria manual y de torque. Conéctate al sistema para revisar stock o facturar en caja rápida.
                  </p>
                  <div className="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <button 
                      onClick={() => setScreen('login')}
                      className="btn btn-warning px-4 py-3 fw-bold text-dark text-uppercase d-flex align-items-center gap-2 shadow-lg"
                      style={{ borderRadius: '8px', backgroundColor: '#f97316', borderColor: '#f97316', color: '#fff' }}
                    >
                      <span>Ingresar al Sistema Administrativo</span>
                      <i className="bi bi-arrow-right-short fs-4"></i>
                    </button>
                    <button 
                      onClick={() => handleQuickLogin('admin')}
                      className="btn btn-outline-light px-4 py-3 fw-semibold text-uppercase"
                      style={{ borderRadius: '8px' }}
                    >
                      <i className="bi bi-lightning-fill text-warning me-2"></i> Operación Rápida
                    </button>
                  </div>
                </div>
                <div className="col-lg-5 d-none d-lg-block text-center text-warning">
                  <i className="bi bi-tools" style={{ fontSize: '13rem', opacity: 0.9 }}></i>
                </div>
              </div>
            </div>
          </header>

          {/* Tarjetas de Beneficios */}
          <section className="py-5 bg-white">
            <div className="container text-center mb-5">
              <span className="text-uppercase fw-bold font-mono text-warning" style={{ color: '#f97316' }}>Módulos del Estudiante</span>
              <h2 className="fw-bold text-dark mt-1">Gestión de Roles de Ferretería</h2>
              <p className="text-muted mx-auto" style={{ maxWidth: '600px' }}>Esta interfaz permite ensayar procesos completos de Auditoría de Almacén y Control de Transacciones de Caja (POS).</p>
            </div>
            <div className="container">
              <div className="row g-4 justify-content-center">
                <div className="col-md-5">
                  <div className="card h-100 p-4 border border-opacity-10 shadow-sm rounded-3">
                    <div className="p-3 bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex mb-3" style={{ width: '56px', height: '56px' }}>
                      <i className="bi bi-boxes fs-3"></i>
                    </div>
                    <h5 className="fw-bold text-dark">Rol: Auditor de Inventario</h5>
                    <p className="text-muted">Ingresa nuevos items, actualiza precios, controla mermas y analiza qué materiales se encuentran bajo el límite óptimo o sin stock.</p>
                  </div>
                </div>
                <div className="col-md-5">
                  <div className="card h-100 p-4 border border-opacity-10 shadow-sm rounded-3">
                    <div className="p-3 bg-success bg-opacity-10 text-success rounded-3 d-inline-flex mb-3" style={{ width: '56px', height: '56px' }}>
                      <i className="bi bi-cart-check fs-3"></i>
                    </div>
                    <h5 className="fw-bold text-dark">Rol: Cajero de Punto de Venta</h5>
                    <p className="text-muted">Carga el carrito interactivo con un clic, asigna el cliente mediante DNI o Cédula, y emite un recibo térmico con desglose de IVA.</p>
                  </div>
                </div>
              </div>
            </div>
          </section>

          {/* Footer */}
          <footer className="bg-dark text-white py-4 mt-auto">
            <div className="container text-center text-muted">
              <p className="mb-1 text-light">Ferretería El Tornillo S.A. &copy; 2026 - Universidad Maquetación Final</p>
              <small>Diseñado con Bootstrap 5 para máxima fidelidad web y facilidad de aprendizaje.</small>
            </div>
          </footer>
        </div>
      )}

      {/* ---------------- PÁGINA: AUTENTICACIÓN / LOGIN ---------------- */}
      {screen === 'login' && (
        <div className="grow d-flex align-items-center justify-content-center py-5 px-3" style={{ backgroundColor: '#f8fafc' }}>
          <div className="card border-0 shadow-lg rounded-3 overflow-hidden bg-white mt-4 mb-4" style={{ maxWidth: '440px', width: '100%', border: '1px solid #e2e8f0' }}>
            
            <div className="text-white text-center p-4" style={{ backgroundColor: '#0f172a', borderBottom: '4px solid #f97316' }}>
              <div className="rounded-circle bg-warning p-2 d-inline-flex mb-2 text-dark shadow-sm" style={{ width: '60px', height: '60px', alignItems: 'center', justifyItems: 'center', justifyContent: 'center' }}>
                <i className="bi bi-nut-fill fs-1"></i>
              </div>
              <h4 className="fw-bold mb-0 tracking-tight">Ferretería El Tornillo</h4>
              <small className="text-uppercase fw-semibold text-warning" style={{ fontSize: '0.75rem', letterSpacing: '1px' }}>Ingreso Seguro</small>
            </div>

            <div className="card-body p-4 p-md-5">
              {loginError && (
                <div className="alert border border-danger bg-danger bg-opacity-10 text-danger text-xs py-2 px-3 shadow-sm mb-3" style={{ fontSize: '0.8rem' }}>
                  <i className="bi bi-exclamation-circle-fill me-2"></i>
                  {loginError}
                </div>
              )}

              <form onSubmit={handleLogin}>
                <div className="mb-3">
                  <label className="form-label fw-semibold text-secondary text-xs mb-1" style={{ fontSize: '0.75rem' }}>CORREO ELECTRÓNICO</label>
                  <div className="input-group">
                    <span className="input-group-text bg-light border-end-0 text-muted"><i className="bi bi-envelope"></i></span>
                    <input 
                      type="email" 
                      className="form-control bg-light border-start-0 ps-0" 
                      placeholder="ejemplo@ferreteria.com"
                      value={loginEmail}
                      onChange={(e) => setLoginEmail(e.target.value)}
                      required
                      id="login-email-input"
                      style={{ boxShadow: 'none' }}
                    />
                  </div>
                </div>

                <div className="mb-4">
                  <div className="d-flex justify-content-between mb-1">
                    <label className="form-label fw-semibold text-secondary text-xs mb-0" style={{ fontSize: '0.75rem' }}>CONTRASEÑA</label>
                    <a href="#" onClick={(e) => { e.preventDefault(); alert('Modo prueba local: La contraseña predeterminada es 123456'); }} className="text-decoration-none text-muted" style={{ fontSize: '0.75rem' }}>¿Olvidaste tu clave?</a>
                  </div>
                  <div className="input-group">
                    <span className="input-group-text bg-light border-end-0 text-muted"><i className="bi bi-lock"></i></span>
                    <input 
                      type="password" 
                      className="form-control bg-light border-start-0 ps-0" 
                      placeholder="••••••••"
                      value={loginPassword}
                      onChange={(e) => setLoginPassword(e.target.value)}
                      required
                      id="login-password-input"
                      style={{ boxShadow: 'none' }}
                    />
                  </div>
                </div>

                <button 
                  type="submit" 
                  className="btn w-100 py-2.5 fw-bold text-dark shadow-sm d-flex align-items-center justify-content-center gap-2"
                  style={{ backgroundColor: '#f97316' }}
                  id="btn-login-submit"
                >
                  <i className="bi bi-box-arrow-in-right fs-5"></i>
                  <span>INGRESAR AL SISTEMA</span>
                </button>
              </form>

              <div className="border-top mt-4 pt-4 text-center">
                <span className="text-muted d-block mb-2" style={{ fontSize: '0.75rem' }}>Acceso rápido para pruebas locales (Click):</span>
                <div className="d-flex justify-content-center gap-2">
                  <button 
                    onClick={() => handleQuickLogin('admin')}
                    className="btn btn-sm btn-dark text-white px-3 py-1.5"
                    style={{ fontSize: '0.8rem', borderRadius: '6px' }}
                    type="button"
                  >
                    <i className="bi bi-person-badge-fill me-1"></i> Admin
                  </button>
                  <button 
                    onClick={() => handleQuickLogin('cajero')}
                    className="btn btn-sm btn-outline-success px-3 py-1.5"
                    style={{ fontSize: '0.8rem', borderRadius: '6px' }}
                    type="button"
                  >
                    <i className="bi bi-cash-coin me-1"></i> Cajero
                  </button>
                </div>
              </div>

              <div className="text-center mt-3">
                <a href="#" onClick={(e) => { e.preventDefault(); setScreen('home'); }} className="text-secondary small text-decoration-none">
                  <i className="bi bi-arrow-left me-1"></i> Volver a la Landing Pública
                </a>
              </div>
            </div>

          </div>
        </div>
      )}

      {/* ---------------- SISTEMA ADMINISTRATIVO (CON SIDEBAR) ---------------- */}
      {isLoggedIn && screen !== 'home' && screen !== 'login' && (
        <div className="d-flex" style={{ minHeight: '80vh' }}>
          
          {/* SIDEBAR FIX GENERADO SEGÚN ROL */}
          <aside className="d-none d-lg-block text-white" style={{ width: '260px', backgroundColor: '#0f172a', flexShrink: 0 }}>
            
            <div className="p-4 border-bottom border-secondary border-opacity-10 d-flex align-items-center gap-2">
              <i className="bi bi-nut-fill text-warning fs-3"></i>
              <div>
                <h6 className="mb-0 fw-bold text-white">El Tornillo</h6>
                <small className="text-muted fw-semibold" style={{ fontSize: '0.7rem' }}>SISTEMA INTERNO</small>
              </div>
            </div>

            <div className="py-3">
              <div className="px-3 mb-2">
                <small className="text-uppercase text-muted fw-bold d-block" style={{ fontSize: '0.65rem', letterSpacing: '0.05em' }}>MÓDULOS</small>
              </div>

              {/* Botón Panel Inicial */}
              <button 
                onClick={() => setScreen('dashboard')}
                className={`w-100 text-start border-0 py-2.5 px-4 d-flex align-items-center gap-2 ${
                  screen === 'dashboard' ? 'text-white' : 'text-secondary bg-transparent'
                }`}
                style={{
                  backgroundColor: screen === 'dashboard' ? 'rgba(249, 115, 22, 0.15)' : 'transparent',
                  borderLeft: screen === 'dashboard' ? '4px solid #f97316' : '4px solid transparent',
                  color: screen === 'dashboard' ? '#fff' : '#94a3b8',
                  transition: '0.15s'
                }}
              >
                <i className="bi bi-speedometer2"></i>
                <span>Panel Inicial</span>
              </button>

              {/* Botón Gestión de Inventario */}
              <button 
                onClick={() => setScreen('inventory')}
                className={`w-100 text-start border-0 py-2.5 px-4 d-flex align-items-center gap-2 ${
                  screen === 'inventory' || screen === 'inventory_form' ? 'text-white' : 'text-secondary bg-transparent'
                }`}
                style={{
                  backgroundColor: (screen === 'inventory' || screen === 'inventory_form') ? 'rgba(249, 115, 22, 0.15)' : 'transparent',
                  borderLeft: (screen === 'inventory' || screen === 'inventory_form') ? '4px solid #f97316' : '4px solid transparent',
                  color: (screen === 'inventory' || screen === 'inventory_form') ? '#fff' : '#94a3b8',
                  transition: '0.15s'
                }}
              >
                <i className="bi bi-boxes"></i>
                <span>Gestión Inventario</span>
              </button>

              {/* Botón Caja POS */}
              <button 
                onClick={() => setScreen('sales_pos')}
                className={`w-100 text-start border-0 py-2.5 px-4 d-flex align-items-center gap-2 ${
                  screen === 'sales_pos' ? 'text-white' : 'text-secondary bg-transparent'
                }`}
                style={{
                  backgroundColor: screen === 'sales_pos' ? 'rgba(249, 115, 22, 0.15)' : 'transparent',
                  borderLeft: screen === 'sales_pos' ? '4px solid #f97316' : '4px solid transparent',
                  color: screen === 'sales_pos' ? '#fff' : '#94a3b8',
                  transition: '0.15s'
                }}
              >
                <i className="bi bi-cart4"></i>
                <span>Punto de Venta (POS)</span>
                <span className="badge bg-warning text-dark ms-auto font-mono text-uppercase" style={{ fontSize: '0.65rem' }}>POS</span>
              </button>

              {/* OPCIONES EXCLUSIVAS DEL ROL ADMINISTRADOR (Visualización Diferenciada de Roles) */}
              {role === 'admin' && (
                <>
                  <div className="px-3 mt-4 mb-2">
                    <small className="text-uppercase text-muted fw-bold d-block" style={{ fontSize: '0.65rem', letterSpacing: '0.05em' }}>ADMINISTRACIÓN</small>
                  </div>
                  
                  <button 
                    onClick={() => setScreen('users')}
                    className={`w-100 text-start border-0 py-2.5 px-4 d-flex align-items-center gap-2 ${
                      screen === 'users' ? 'text-white' : 'text-secondary bg-transparent'
                    }`}
                    style={{
                      backgroundColor: screen === 'users' ? 'rgba(249, 115, 22, 0.15)' : 'transparent',
                      borderLeft: screen === 'users' ? '4px solid #f97316' : '4px solid transparent',
                      color: screen === 'users' ? '#fff' : '#94a3b8',
                      transition: '0.15s'
                    }}
                  >
                    <i className="bi bi-people"></i>
                    <span>Gestionar Usuarios</span>
                  </button>

                  <button 
                    onClick={() => setScreen('reports')}
                    className={`w-100 text-start border-0 py-2.5 px-4 d-flex align-items-center gap-2 ${
                      screen === 'reports' ? 'text-white' : 'text-secondary bg-transparent'
                    }`}
                    style={{
                      backgroundColor: screen === 'reports' ? 'rgba(249, 115, 22, 0.15)' : 'transparent',
                      borderLeft: screen === 'reports' ? '4px solid #f97316' : '4px solid transparent',
                      color: screen === 'reports' ? '#fff' : '#94a3b8',
                      transition: '0.15s'
                    }}
                  >
                    <i className="bi bi-graph-up-arrows"></i>
                    <span>Reportes y Ventas</span>
                  </button>
                </>
              )}

              <div className="px-3 mt-4 mb-2">
                <small className="text-uppercase text-muted fw-bold d-block" style={{ fontSize: '0.65rem', letterSpacing: '0.05em' }}>SISTEMA</small>
              </div>

              <button 
                onClick={() => setScreen('home')}
                className="w-100 text-start border-0 py-2 px-4 d-flex align-items-center gap-2 text-secondary bg-transparent"
                style={{ color: '#94a3b8', fontSize: '0.9rem' }}
              >
                <i className="bi bi-globe"></i>
                <span>Sitio Público</span>
              </button>

              <div className="px-3 mt-4">
                <button 
                  onClick={handleLogout}
                  className="btn btn-outline-danger w-100 py-1.5 d-flex align-items-center justify-content-center gap-1"
                  style={{ fontSize: '0.85rem' }}
                >
                  <i className="bi bi-box-arrow-left"></i>
                  Cerrar Sesión
                </button>
              </div>

            </div>
          </aside>

          {/* CONTENEDOR CONTENIDO ACTIVO */}
          <div className="grow d-flex flex-column" style={{ minWidth: 0 }}>
            
            {/* Header Interno */}
            <header className="navbar navbar-expand navbar-light bg-white border-bottom py-2.5 px-4 shadow-sm">
              <div className="container-fluid p-0 d-flex justify-content-between align-items-center">
                
                {/* Indicación de navegación móvil rápida */}
                <div className="d-flex align-items-center gap-2 d-lg-none">
                  <i className="bi bi-nut-fill text-warning fs-4"></i>
                  <select 
                    id="mobile-screen-select"
                    className="form-select form-select-sm fw-bold border-0 bg-transparent text-primary py-0"
                    value={screen}
                    onChange={(e) => setScreen(e.target.value as Screen)}
                    style={{ outline: 'none', boxShadow: 'none' }}
                    aria-label="Seleccionar pantalla"
                  >
                    <option value="dashboard">Panel Inicial</option>
                    <option value="inventory">Gestión Inventario</option>
                    <option value="sales_pos">Punto de Venta (POS)</option>
                  </select>
                </div>

                {/* Buscador de encabezado */}
                <div className="d-none d-lg-block text-muted" style={{ fontSize: '0.9rem' }}>
                  <i className="bi bi-building-fill me-1"></i> Sucursal Central
                </div>

                {/* Perfil del Operador */}
                <div className="d-flex align-items-center gap-3 ms-auto">
                  <div className="text-end d-none d-md-block">
                    <span className="fw-bold text-dark d-block" style={{ fontSize: '0.85rem' }}>{activeUser}</span>
                    <small className="text-muted text-uppercase d-block fw-semibold" style={{ fontSize: '0.65rem', color: '#f97316' }}>
                      Nivel:{' '}
                      <span className={`badge ${role === 'admin' ? 'bg-primary' : 'bg-success'}`}>
                        {role === 'admin' ? 'Administrador' : 'Cajero'}
                      </span>
                    </small>
                  </div>
                  <div className="rounded-circle text-white d-flex align-items-center justify-content-center bg-dark" style={{ width: '38px', height: '38px' }}>
                    <i className="bi bi-person-fill"></i>
                  </div>
                  <button 
                    onClick={handleLogout}
                    className="btn btn-sm btn-outline-secondary d-md-none"
                    title="Cerrar sesión"
                  >
                    <i className="bi bi-box-arrow-left"></i>
                  </button>
                </div>

              </div>
            </header>

            {/* VISTAS ACCIONES */}
            <main className="container-fluid py-4 px-md-4 grow bg-light">
              
              {/* ---------------- VISTA: 1. DASHBOARD ---------------- */}
              {screen === 'dashboard' && (
                <div>
                  <div className="row align-items-center mb-4">
                    <div className="col-12 col-md-8">
                      <h4 className="fw-extrabold text-dark mb-1">¡Buenos días, {role === 'admin' ? 'Administrador Yahir' : 'Cajero Carlos'}!</h4>
                      <p className="text-muted small">Este es el reporte del estado físico y transaccional actual en "El Tornillo".</p>
                    </div>
                  </div>

                  {/* Bento Cards Métricas */}
                  <div className="row g-3 mb-4">
                    <div className="col-sm-6 col-xl-3">
                      <div className="card h-100 shadow-sm border-start border-primary border-4 rounded-3 p-3">
                        <div className="d-flex align-items-center justify-content-between mb-2">
                          <span className="text-muted text-uppercase fw-bold text-xs" style={{ fontSize: '0.7rem' }}>Ventas de Hoy</span>
                          <i className="bi bi-currency-dollar text-primary fs-3"></i>
                        </div>
                        <h4 className="fw-extrabold font-mono text-dark">${stats.salesTotal.toFixed(2)}</h4>
                        <span className="text-success small fw-semibold">
                          <i className="bi bi-arrow-up-right"></i> {stats.ordersCount} Boletas registradas
                        </span>
                      </div>
                    </div>

                    <div className="col-sm-6 col-xl-3">
                      <div className="card h-100 shadow-sm border-start border-warning border-4 rounded-3 p-3">
                        <div className="d-flex align-items-center justify-content-between mb-2">
                          <span className="text-muted text-uppercase fw-bold text-xs" style={{ fontSize: '0.7rem' }}>Modelos en Almacén</span>
                          <i className="bi bi-boxes text-warning fs-3"></i>
                        </div>
                        <h4 className="fw-extrabold font-mono text-dark">{stats.totalProducts}</h4>
                        <span className="text-muted small">Códigos SKU activos</span>
                      </div>
                    </div>

                    <div className="col-sm-6 col-xl-3">
                      <div className="card h-100 shadow-sm border-start border-danger border-4 rounded-3 p-3">
                        <div className="d-flex align-items-center justify-content-between mb-2">
                          <span className="text-muted text-uppercase fw-bold text-xs" style={{ fontSize: '0.7_rem' }}>Stock Crítico</span>
                          <i className="bi bi-exclamation-triangle text-danger fs-3"></i>
                        </div>
                        <h4 className="fw-extrabold font-mono text-danger">{stats.lowStockCount}</h4>
                        <span className="text-danger small fw-semibold">Bajo el límite óptimo</span>
                      </div>
                    </div>

                    <div className="col-sm-6 col-xl-3">
                      <div className="card h-100 shadow-sm border-start border-dark border-4 rounded-3 p-3">
                        <div className="d-flex align-items-center justify-content-between mb-2">
                          <span className="text-muted text-uppercase fw-bold text-xs" style={{ fontSize: '0.7_rem' }}>Agotamiento Crítico o Total</span>
                          <i className="bi bi-shield-x text-dark fs-3"></i>
                        </div>
                        {/* REGLA VISUAL: Si stock es 0, mostrar etiqueta roja */}
                        <div className="d-flex align-items-center gap-2">
                          <h4 className="fw-extrabold font-mono text-dark mb-0">{stats.outOfStockCount}</h4>
                          <span className="badge bg-danger text-white" style={{ fontSize: '0.7rem' }}>Sin Stock</span>
                        </div>
                        <span className="text-muted small">Requiere cotización de urgencia</span>
                      </div>
                    </div>
                  </div>

                  {/* Call to Actions rápidas en panel */}
                  <div className="row g-3 mb-4">
                    <div className="col-md-6">
                      <div className="card p-4 text-white border-0 shadow-sm" style={{ backgroundColor: '#1e3a8a', borderRadius: '12px' }}>
                        <h5 className="fw-bold"><i className="bi bi-telephone-fill text-warning me-2"></i>Módulo Punto de Venta (POS)</h5>
                        <p className="small mb-4 opacity-75">Modulo POS optimizado para mostrador. Excelente para cajeros, calcula IVA del 12% automáticamente, y limpia el stock de bodega en tiempo real.</p>
                        <button onClick={() => setScreen('sales_pos')} className="btn btn-warning fw-bold text-dark px-4" id="goto-sales-pos">
                          Abre Caja POS e inicia Ventas <i className="bi bi-chevron-right"></i>
                        </button>
                      </div>
                    </div>
                    <div className="col-md-6">
                      <div className="card p-4 text-white border-0 shadow-sm" style={{ backgroundColor: '#1e293b', borderRadius: '12px' }}>
                        <h5 className="fw-bold"><i className="bi bi-gear-wide-connected text-warning me-2"></i>Gestionar Materiales</h5>
                        <p className="small mb-4 opacity-75">Módulo de control de existencias, adiciones físicas, modificación de precios públicos y listado estructurado de repuestos de construcción.</p>
                        <button onClick={() => setScreen('inventory')} className="btn btn-outline-light px-4">
                          Ir a Inventario de Bodega <i className="bi bi-chevron-right"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Tabla del HISTORIAL DE TRANSACCIONES DE HOY */}
                  <div className="card border-0 shadow-sm rounded-3">
                    <div className="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                      <h5 className="fw-bold text-dark mb-0"><i className="bi bi-receipt-cutoff me-2 text-primary"></i>Boletas de Ventas del Turno</h5>
                      <span className="badge bg-success-subtle text-success py-1.5 px-3">Turno Abierto</span>
                    </div>
                    <div className="card-body p-0">
                      <div className="table-responsive">
                        <table className="table table-hover align-middle mb-0">
                          <thead className="table-light">
                            <tr className="text-muted text-uppercase fw-semibold" style={{ fontSize: '0.72rem' }}>
                              <th className="px-4 py-3">Código Factura</th>
                              <th className="py-3">Cliente</th>
                              <th className="py-3">Fecha y Hora</th>
                              <th className="py-3">Cajero</th>
                              <th className="py-3 text-center">Método</th>
                              <th className="py-3 text-end px-4">Monto Cobrado</th>
                            </tr>
                          </thead>
                          <tbody>
                            {transactions.map(tx => (
                              <tr key={tx.id}>
                                <td className="px-4 py-3 font-mono text-primary fw-bold">{tx.invoiceNumber}</td>
                                <td>
                                  <span className="fw-semibold text-dark">{tx.customerName}</span>
                                  <small className="text-muted d-block" style={{ fontSize: '0.75rem' }}>Identificación: {tx.customerDni}</small>
                                </td>
                                <td style={{ fontSize: '0.82rem' }}>{tx.date}</td>
                                <td><span className="badge bg-light text-secondary border">{tx.cashier}</span></td>
                                <td className="text-center">
                                  <span className={`badge py-1 px-2.5 text-uppercase ${
                                    tx.paymentMethod === 'efectivo' ? 'bg-success-subtle text-success border border-success border-opacity-10' :
                                    tx.paymentMethod === 'tarjeta' ? 'bg-info-subtle text-info border border-info border-opacity-10' :
                                    'bg-primary-subtle text-primary border border-primary border-opacity-10'
                                  }`} style={{ fontSize: '0.7rem' }}>
                                    {tx.paymentMethod}
                                  </span>
                                </td>
                                <td className="text-end px-4 font-mono fw-bold text-dark">${tx.total.toFixed(2)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* ---------------- VISTA: 2. INVENTARIO ---------------- */}
              {screen === 'inventory' && (
                <div>
                  
                  {/* Fila superior */}
                  <div className="row align-items-center mb-4 g-3">
                    <div className="col-12 col-md-6">
                      <h4 className="fw-extrabold text-dark mb-1">Módulo de Almacén General de Bodega</h4>
                      <p className="text-muted small mb-0">Inspecciona y edita la clavería, bolsas de cemento, herramientas manuales y eléctricas.</p>
                    </div>
                    
                    <div className="col-12 col-md-6 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
                      {/* EXPORTACIÓN SÓLO BOTONES UI COMO SE SOLICITA */}
                      <button 
                        onClick={() => {
                          const doc = new jsPDF();
                          doc.text('Inventario - Ferretería El Tornillo S.A.', 14, 15);
                          autoTable(doc, {
                            startY: 20,
                            head: [['SKU', 'Producto', 'Categoría', 'Precio', 'Stock', 'Unidad']],
                            body: products.map(p => [p.sku, p.name, p.category, `$${p.price.toFixed(2)}`, p.stock.toString(), p.unit]),
                          });
                          doc.save('Inventario_Ferreteria.pdf');
                        }}
                        className="btn btn-outline-danger btn-sm fw-semibold d-flex align-items-center gap-1 bg-white"
                        id="btn-export-pdf"
                      >
                        <i className="bi bi-filetype-pdf text-danger fs-5"></i>
                        <span>Exportar PDF</span>
                      </button>

                      <button 
                        onClick={() => {
                          const wsData = [
                            ['SKU', 'Producto', 'Categoría', 'Precio Unitario', 'Stock Disponible', 'Unidad de Medida'],
                            ...products.map(p => [p.sku, p.name, p.category, p.price, p.stock, p.unit])
                          ];
                          const ws = XLSX.utils.aoa_to_sheet(wsData);
                          const wb = XLSX.utils.book_new();
                          XLSX.utils.book_append_sheet(wb, ws, "Inventario");
                          XLSX.writeFile(wb, "Inventario_Ferreteria.xlsx");
                        }}
                        className="btn btn-outline-success btn-sm fw-semibold d-flex align-items-center gap-1 bg-white"
                        id="btn-export-excel"
                      >
                        <i className="bi bi-filetype-xlsx text-success fs-5"></i>
                        <span>Exportar Excel</span>
                      </button>

                      <button 
                        onClick={handleAddNewClick}
                        className="btn btn-primary d-flex align-items-center gap-1 fw-bold"
                        id="btn-add-new-product"
                      >
                        <i className="bi bi-plus-circle"></i>
                        <span>Nuevo Producto</span>
                      </button>
                    </div>
                  </div>

                  {/* Buscador y selectores */}
                  <div className="card border-0 shadow-sm mb-4">
                    <div className="card-body p-3">
                      <div className="row g-2">
                        
                        <div className="col-md-5">
                          <div className="input-group input-group-sm">
                            <span className="input-group-text bg-white text-muted"><i className="bi bi-search"></i></span>
                            <input 
                              type="text" 
                              className="form-control" 
                              placeholder="Filtrar por código SKU, nombre o marca..."
                              value={inventorySearch}
                              onChange={(e) => setInventorySearch(e.target.value)}
                            />
                          </div>
                        </div>

                        <div className="col-md-3">
                          <select 
                            id="inventory-category-filter"
                            className="form-select form-select-sm"
                            value={inventoryCategory}
                            onChange={(e) => setInventoryCategory(e.target.value)}
                            aria-label="Filtrar por categoría"
                          >
                            <option value="">Todas las Categorías</option>
                            <option value="Fijaciones y Tornillos">Fijaciones y Tornillos</option>
                            <option value="Construcción y Áridos">Construcción y Áridos</option>
                            <option value="Herramientas Eléctricas">Herramientas Eléctricas</option>
                            <option value="Herramientas Manuales">Herramientas Manuales</option>
                            <option value="Plomería">Plomería e Hidráulica</option>
                            <option value="Pinturas">Pinturas</option>
                          </select>
                        </div>

                        <div className="col-md-2">
                          <select 
                            id="inventory-stock-filter"
                            className="form-select form-select-sm"
                            value={inventoryStockFilter}
                            onChange={(e) => setInventoryStockFilter(e.target.value)}
                            aria-label="Filtrar por nivel de stock"
                          >
                            <option value="">Todos los Stocks</option>
                            <option value="disponible">Suficiente (&gt; 8)</option>
                            <option value="bajo">Bajo Stock (1 a 8)</option>
                            <option value="sin_stock">Agotado (0 unidades)</option>
                          </select>
                        </div>

                        <div className="col-md-2">
                          <button 
                            className="btn btn-sm btn-dark w-100"
                            onClick={() => { setInventorySearch(''); setInventoryCategory(''); setInventoryStockFilter(''); }}
                          >
                            <i className="bi bi-arrow-counterclockwise"></i> Limpiar
                          </button>
                        </div>

                      </div>
                    </div>
                  </div>

                  {/* Tabla del Inventario */}
                  <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div className="table-responsive">
                      <table className="table table-hover align-middle mb-0 text-dark" id="inventoryTable">
                        <thead className="table-light">
                          <tr className="text-muted text-uppercase fw-bold" style={{ fontSize: '0.7rem' }}>
                            <th className="px-4 py-3">Código SKU</th>
                            <th className="py-3">Ficha de Producto</th>
                            <th className="py-3 text-center">Categoría</th>
                            <th className="py-3 text-end">Precio Venta</th>
                            <th className="py-3 text-center">Inventario Físico</th>
                            <th className="py-3 text-center">Alerta Stock</th>
                            <th className="py-3 text-end px-4">Acciones</th>
                          </tr>
                        </thead>
                        <tbody>
                          {filteredProducts.length === 0 ? (
                            <tr>
                              <td colSpan={7} className="text-center py-5 text-muted">
                                <i className="bi bi-info-circle fs-3 d-block mb-2"></i>
                                No se encontraron productos con los criterios seleccionados.
                              </td>
                            </tr>
                          ) : (
                            filteredProducts.map(p => {
                              const isOutOfStock = p.stock === 0;
                              const isLowStock = p.stock > 0 && p.stock <= 8;
                              
                              return (
                                <tr key={p.id}>
                                  <td className="px-4 py-3 font-mono fw-bold" style={{ fontSize: '0.85rem' }}>{p.sku}</td>
                                  <td>
                                    <div className={`fw-bold ${isOutOfStock ? 'text-decoration-line-through text-muted' : 'text-dark'}`}>{p.name}</div>
                                    <small className="text-muted d-block" style={{ fontSize: '0.75rem', maxWidth: '380px' }}>{p.description}</small>
                                    {p.brand && <span className="badge bg-light text-muted border text-xs" style={{ fontSize: '0.65rem' }}>Marca: {p.brand}</span>}
                                  </td>
                                  <td className="text-center">
                                    <span className="badge bg-light text-dark border">{p.category}</span>
                                  </td>
                                  <td className="text-end font-mono fw-bold">${p.price.toFixed(2)}</td>
                                  <td className="text-center font-mono fw-bold">
                                    <span className={isOutOfStock ? 'text-danger' : isLowStock ? 'text-warning' : 'text-dark'}>
                                      {p.stock} {p.unit || 'un'}
                                    </span>
                                  </td>
                                  <td className="text-center">
                                    {isOutOfStock ? (
                                      // REGLA VISUAL DE STOCK 0 - ETIQUETA ROJA OBLIGATORIA
                                      <span className="badge bg-danger text-white py-1 px-2.5 uppercase" style={{ fontSize: '0.7rem' }}>
                                        <i className="bi bi-shield-slash-fill me-1"></i> Sin stock
                                      </span>
                                    ) : isLowStock ? (
                                      <span className="badge bg-warning-subtle text-warning border border-warning border-opacity-20 py-1 px-2.5 fw-semibold" style={{ fontSize: '0.7rem' }}>
                                        <i className="bi bi-exclamation-triangle-fill me-1"></i> Stock Critico
                                      </span>
                                    ) : (
                                      <span className="badge bg-success-subtle text-success border border-success border-opacity-10 py-1 px-2.5 fw-semibold" style={{ fontSize: '0.7rem' }}>
                                        <i className="bi bi-check-circle-fill me-1"></i> Disponible
                                      </span>
                                    )}
                                  </td>
                                  <td className="text-end px-4">
                                    <div className="btn-group btn-group-sm">
                                      <button 
                                        onClick={() => handleEditClick(p)}
                                        className="btn btn-outline-secondary"
                                        title="Editar Ficha"
                                      >
                                        <i className="bi bi-pencil-square"></i>
                                      </button>
                                      <button 
                                        onClick={() => handleDeleteProduct(p.id, p.sku)}
                                        className="btn btn-outline-danger"
                                        title="Eliminar del Almacén"
                                      >
                                        <i className="bi bi-trash"></i>
                                      </button>
                                    </div>
                                  </td>
                                </tr>
                              );
                            })
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              )}

              {/* ---------------- VISTA: 3. FORMULARIOS ---------------- */}
              {screen === 'inventory_form' && (
                <div style={{ maxWidth: '850px', margin: '0 auto' }}>
                  
                  <div className="d-flex align-items-center gap-2 mb-4">
                    <button onClick={() => setScreen('inventory')} className="btn btn-outline-secondary btn-sm px-3">
                      <i className="bi bi-arrow-left"></i> Volver
                    </button>
                    <h4 className="fw-bold text-dark mb-0">
                      {selectedProduct ? `Editar Ficha: ${selectedProduct.sku}` : 'Ingresar Nuevo Producto a Bodega'}
                    </h4>
                  </div>

                  <div className="card border-0 shadow-sm rounded-3">
                    <div className="card-header bg-white border-bottom py-3">
                      <h6 className="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                        <i className="bi bi-clipboard-data-fill text-warning"></i>
                        Información Detallada de Materiales
                      </h6>
                    </div>

                    <div className="card-body p-4 p-md-5">
                      {formError && (
                        <div className="alert alert-danger font-medium border-0 py-2.5 text-xs mb-4">
                          <i className="bi bi-exclamation-octagon-fill me-2"></i>
                          {formError}
                        </div>
                      )}

                      <form onSubmit={handleSaveProduct}>
                        <div className="row g-3">
                          
                          <div className="col-md-6">
                            <label className="form-label text-dark fw-semibold small mb-1">CÓDIGO SKU / REF *</label>
                            <input 
                              type="text" 
                              className="form-control font-mono text-uppercase"
                              placeholder="Ej: SKU-CEME-431"
                              value={formSku}
                              onChange={(e) => setFormSku(e.target.value)}
                              required
                            />
                            <small className="text-muted text-xs">Identificador físico del material para ventas rápidas.</small>
                          </div>

                          <div className="col-md-6">
                            <label htmlFor="form-category" className="form-label text-dark fw-semibold small mb-1">CATEGORÍA DEL MATERIAL *</label>
                            <select 
                              id="form-category"
                              className="form-select text-dark"
                              value={formCategory}
                              onChange={(e) => setFormCategory(e.target.value)}
                              required
                              aria-label="Seleccionar categoría del material"
                              title="Seleccionar categoría del material"
                            >
                              <option value="Fijaciones y Tornillos">Fijaciones y Tornillos</option>
                              <option value="Construcción y Áridos">Construcción y Áridos</option>
                              <option value="Herramientas Eléctricas">Herramientas Eléctricas</option>
                              <option value="Herramientas Manuales">Herramientas Manuales</option>
                              <option value="Plomería">Plomería e Hidráulica</option>
                              <option value="Pinturas">Pinturas</option>
                            </select>
                          </div>

                          <div className="col-12">
                            <label className="form-label text-dark fw-semibold small mb-1">NOMBRE TÉCNICO COMPLETO *</label>
                            <input 
                              type="text" 
                              className="form-control"
                              placeholder="Ej: Cemento Gris Portland Especial (50Kg)"
                              value={formName}
                              onChange={(e) => setFormName(e.target.value)}
                              required
                              aria-label="Nombre técnico completo"
                            />
                          </div>

                          <div className="col-md-6">
                            <label className="form-label text-dark fw-semibold small mb-1">MARCA FABRICANTE</label>
                            <input 
                              type="text" 
                              className="form-control"
                              placeholder="Ej: Stanley, Bosch, Holcim..."
                              value={formBrand}
                              onChange={(e) => setFormBrand(e.target.value)}
                              aria-label="Marca fabricante"
                            />
                          </div>

                          <div className="col-md-6">
                            <label className="form-label text-dark fw-semibold small mb-1">UNIDAD DE BODEGA *</label>
                            <select 
                              id="form-unit"
                              className="form-select"
                              value={formUnit}
                              onChange={(e) => setFormUnit(e.target.value)}
                              required
                              aria-label="Seleccionar unidad de bodega"
                            >
                              <option value="Unidades">Unidades (Un)</option>
                              <option value="Bolsas">Bolsas (Saco)</option>
                              <option value="Rollos">Rollos (Mts)</option>
                              <option value="Cajas">Cajas (Cj)</option>
                              <option value="Kilogramos">Kilos (Kg)</option>
                            </select>
                          </div>

                          <div className="col-md-6">
                            <label className="form-label text-dark fw-semibold small mb-1">PRECIO PÚBLICO SUGERIDO *</label>
                            <div className="input-group">
                              <span className="input-group-text bg-light text-secondary">$</span>
                              <input 
                                type="number" 
                                step="0.01"
                                className="form-control font-mono"
                                value={formPrice || ''}
                                onChange={(e) => setFormPrice(Number(e.target.value))}
                                required
                                aria-label="Precio público sugerido"
                                title="Precio público sugerido"
                              />
                            </div>
                          </div>

                          <div className="col-md-6">
                            <label className="form-label text-dark fw-semibold small mb-1">STOCK INICIAL FISICO EN ESTANTES *</label>
                            <input 
                              type="number"
                              className="form-control font-mono"
                              value={formStock}
                              onChange={(e) => setFormStock(Number(e.target.value))}
                              required
                              min="0"
                              aria-label="Stock inicial físico en estantes"
                            />
                          </div>

                          <div className="col-12">
                            <label className="form-label text-dark fw-semibold small mb-1">DESCRIPCIÓN DE ALMACENAMIENTO O ESPECIFICACIONES</label>
                            <textarea 
                              className="form-control"
                              rows={3}
                              placeholder="Detalles sobre pesos, resistencias, voltajes, o alertas de humedad..."
                              value={formDescription}
                              onChange={(e) => setFormDescription(e.target.value)}
                            ></textarea>
                          </div>

                        </div>

                        <div className="border-top pt-4 mt-4 d-flex justify-content-end gap-2">
                          <button 
                            type="button" 
                            onClick={() => setScreen('inventory')} 
                            className="btn btn-light px-4 border text-dark font-medium"
                          >
                            Cancelar
                          </button>
                          
                          <button 
                            type="submit" 
                            className="btn btn-warning px-4 fw-bold text-dark d-flex align-items-center gap-1 shadow-sm"
                            style={{ backgroundColor: '#f97316', borderColor: '#f97316', color: '#fff' }}
                            id="btn-save-product-form"
                          >
                            <i className="bi bi-save-fill"></i>
                            <span>{selectedProduct ? 'Guardar Cambios' : 'Ingresar al Almacén'}</span>
                          </button>
                        </div>
                      </form>

                    </div>
                  </div>

                </div>
              )}

              {/* ---------------- VISTA: 4. MÓDULO VENTAS / POS ---------------- */}
              {screen === 'sales_pos' && (
                <div>
                  
                  <div className="row g-4">
                    {/* Caja Izquierda: Catálogo del POS */}
                    <div className="col-lg-7">
                      <div className="card border-0 shadow-sm h-100 rounded-3">
                        <div className="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                          <h6 className="fw-bold text-secondary-dark mb-0 d-flex align-items-center gap-2">
                            <i className="bi bi-boxes text-warning"></i>
                            Disponibles en Mostrador
                          </h6>
                          <span className="badge bg-primary font-mono select-none" style={{ fontSize: '0.7rem' }}>CATÁLOGO INTEGRADO</span>
                        </div>

                        <div className="card-body p-3">
                          {/* Buscador POS */}
                          <div className="row g-2 mb-3">
                            <div className="col-sm-7">
                              <div className="input-group input-group-sm">
                                <span className="input-group-text bg-white border-end-0 text-muted"><i className="bi bi-search"></i></span>
                                <input 
                                  type="text" 
                                  className="form-control border-start-0"
                                  placeholder="Busca pernos, cementos, herramientas..."
                                  value={posSearch}
                                  onChange={(e) => setPosSearch(e.target.value)}
                                />
                              </div>
                            </div>
                            <div className="col-sm-5">
                              <select 
                                id="pos-category-filter"
                                className="form-select form-select-sm"
                                value={posCategory}
                                onChange={(e) => setPosCategory(e.target.value)}
                                aria-label="Filtrar por categoría POS"
                              >
                                <option value="">Todas las Familias</option>
                                <option value="Fijaciones y Tornillos">Fijaciones y Tornillos</option>
                                <option value="Construcción y Áridos">Construcción y Áridos</option>
                                <option value="Herramientas Eléctricas">Herramientas Eléctricas</option>
                                <option value="Herramientas Manuales">Herramientas Manuales</option>
                                <option value="Plomería">Plomería</option>
                                <option value="Pinturas">Pinturas</option>
                              </select>
                            </div>
                          </div>

                          {/* Lista Grid */}
                          <div className="row g-2" style={{ maxHeight: '460px', overflowY: 'auto', alignContent: 'flex-start' }}>
                            {posFilteredProducts.length === 0 ? (
                              <div className="col-12 text-center py-5 text-muted">
                                No hay productos en Almacén bajo este parámetro.
                              </div>
                            ) : (
                              posFilteredProducts.map(p => {
                                const isOutOfStock = p.stock === 0;
                                const isLowStock = p.stock > 0 && p.stock <= 8;
                                
                                return (
                                  <div className="col-md-6" key={p.id}>
                                    <div className={`card h-100 p-2.5 border rounded-3 position-relative d-flex flex-column ${
                                      isOutOfStock ? 'bg-light opacity-75' : 'bg-white shadow-none hover:shadow-sm'
                                    }`} style={{ transition: 'all 0.15s' }}>
                                      
                                      <div className="d-flex align-items-center justify-content-between mb-1.5" style={{ fontSize: '0.75rem' }}>
                                        <span className="badge bg-light text-muted font-mono">{p.sku}</span>
                                        {isOutOfStock ? (
                                          <span className="badge bg-danger text-white">Agotado</span>
                                        ) : isLowStock ? (
                                          <span className="badge bg-warning text-dark">Bajo Stock</span>
                                        ) : (
                                          <span className="badge bg-success-subtle text-success">Ok</span>
                                        )}
                                      </div>

                                      <h6 className="fw-bold mb-1 text-dark" style={{ fontSize: '0.85rem', lineHeight: '1.2' }}>{p.name}</h6>
                                      <p className="text-muted mb-2 text-truncate" style={{ fontSize: '0.72rem' }}>{p.description}</p>
                                      
                                      <div className="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                        <div>
                                          <span className="fw-extrabold fs-6 text-dark font-mono">${p.price.toFixed(2)}</span>
                                          <small className="text-secondary d-block" style={{ fontSize: '0.67rem' }}>Unid: {p.unit}</small>
                                        </div>

                                        <button 
                                          className={`btn btn-xs py-1 px-2.5 fw-bold d-flex align-items-center gap-1 ${
                                            isOutOfStock ? 'btn-secondary disabled' : 'btn-warning text-dark'
                                          }`}
                                          style={{ fontSize: '0.78rem', borderRadius: '5px' }}
                                          onClick={() => handleAddToCart(p)}
                                          disabled={isOutOfStock}
                                          id={`pos-add-btn-${p.id}`}
                                        >
                                          <i className="bi bi-plus-lg"></i>
                                          <span>Agregar</span>
                                        </button>
                                      </div>
                                    </div>
                                  </div>
                                );
                              })
                            )}
                          </div>

                        </div>
                      </div>
                    </div>

                    {/* Caja Derecha: Carrito / Facturación del Cliente */}
                    <div className="col-lg-5">
                      <div className="card border-0 shadow-sm rounded-3">
                        <div className="card-header bg-dark text-white py-3 border-bottom d-flex align-items-center justify-content-between" style={{ backgroundColor: '#0f172a' }}>
                          <h6 className="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                            <i className="bi bi-receipt-cutoff text-warning"></i>
                            Resumen de la Venta
                          </h6>
                          <span className="badge bg-warning text-dark font-mono">BOLETA</span>
                        </div>

                        <div className="card-body p-3">
                          
                          {/* Datos Identificación del Cliente */}
                          <div className="bg-light p-2.5 rounded-3 border mb-3">
                            <span className="text-xs text-muted fw-bold d-block mb-1.5 uppercase" style={{ fontSize: '0.68rem', letterSpacing: '0.05em' }}>CLIENTE IDENTIFICACIÓN (OBLIGATORIA)</span>
                            <div className="row g-1">
                              <div className="col-5">
                                <input 
                                  type="text" 
                                  className="form-control form-control-sm font-mono text-dark"
                                  placeholder="DNI / RUC"
                                  value={customerDni}
                                  onChange={(e) => setCustomerDni(e.target.value)}
                                  id="customer-dni-input"
                                />
                              </div>
                              <div className="col-7">
                                <input 
                                  type="text" 
                                  className="form-control form-control-sm text-dark"
                                  placeholder="Escriba el nombre del cliente"
                                  value={customerName}
                                  onChange={(e) => setCustomerName(e.target.value)}
                                  id="customer-name-input"
                                />
                              </div>
                            </div>
                            
                            <div className="mt-2 d-flex align-items-center justify-content-between">
                              <span className="text-xs text-muted">Pago:</span>
                              <div className="d-flex gap-2">
                                <label className="text-xs d-flex align-items-center gap-1 cursor-pointer">
                                  <input 
                                    type="radio" 
                                    name="payment_method" 
                                    checked={paymentMethod === 'efectivo'} 
                                    onChange={() => setPaymentMethod('efectivo')}
                                  /> Efectivo
                                </label>
                                <label className="text-xs d-flex align-items-center gap-1 cursor-pointer">
                                  <input 
                                    type="radio" 
                                    name="payment_method" 
                                    checked={paymentMethod === 'tarjeta'} 
                                    onChange={() => setPaymentMethod('tarjeta')}
                                  /> Tarjeta
                                </label>
                              </div>
                            </div>
                          </div>

                          {/* Listado de Productos Cargados */}
                          <div className="mb-3">
                            <span className="text-muted fw-bold d-block mb-1 text-xs uppercase" style={{ fontSize: '0.68rem' }}>PRODUCTOS ELEGIDOS</span>
                            
                            <div className="border rounded-3 p-2.5 bg-light" style={{ maxHeight: '220px', overflowY: 'auto' }}>
                              {cart.length === 0 ? (
                                <div className="text-center py-4 text-muted small">
                                  <i className="bi bi-cart3 d-block fs-4 mb-1"></i>
                                  Caja vacía. Agregue productos de mostrador.
                                </div>
                              ) : (
                                cart.map(item => (
                                  <div key={item.product.id} className="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom text-dark">
                                    <div className="pe-2" style={{ maxWidth: '65%' }}>
                                      <span className="fw-bold d-block text-truncate" style={{ fontSize: '0.85rem' }}>{item.product.name}</span>
                                      <small className="text-muted font-mono" style={{ fontSize: '0.75rem' }}>${item.product.price.toFixed(2)} x {item.product.unit}</small>
                                    </div>
                                    <div className="d-flex align-items-center gap-1.5">
                                      <input 
                                        type="number" 
                                        className="form-control form-control-sm text-center p-0 font-mono fw-bold"
                                        style={{ width: '45px', height: '26px', fontSize: '0.82rem' }}
                                        value={item.quantity}
                                        onChange={(e) => updateCartQuantity(item.product.id, Number(e.target.value), item.product.stock)}
                                        min="1"
                                        aria-label="Cantidad de producto"
                                        title="Cantidad de producto"
                                      />
                                      <button 
                                        onClick={() => removeFromCart(item.product.id)}
                                        className="btn btn-sm btn-outline-danger p-1 line-height-none border-0"
                                        title="Quitar producto del carrito"
                                        aria-label="Quitar producto del carrito"
                                      >
                                        <i className="bi bi-trash-fill"></i>
                                      </button>
                                    </div>
                                  </div>
                                ))
                              )}
                            </div>
                          </div>

                          {/* Análisis de Cobro */}
                          <div className="pt-2 border-top text-dark" style={{ fontSize: '0.85rem' }}>
                            <div className="d-flex justify-content-between align-items-center mb-1">
                              <span className="text-secondary">Subtotal Neto:</span>
                              <span className="fw-semibold font-mono" id="val-pos-subtotal">${cartSubtotal.toFixed(2)}</span>
                            </div>
                            <div className="d-flex justify-content-between align-items-center mb-1">
                              <span className="text-secondary">Impuesto IVA (12%):</span>
                              <span className="fw-semibold font-mono" id="val-pos-tax">${cartTax.toFixed(2)}</span>
                            </div>
                            <hr className="my-1.5 text-muted opacity-25" />
                            <div className="d-flex justify-content-between align-items-center mb-3">
                              <span className="fw-bold h6 mb-0 text-dark">TOTAL COBRAR:</span>
                              <span className="fw-extrabold h5 mb-0 text-primary font-mono" id="val-pos-total">${cartTotal.toFixed(2)}</span>
                            </div>
                          </div>

                          <button 
                            onClick={handleFinalizeSale}
                            className="btn btn-warning w-100 py-3 fw-bold text-dark d-flex align-items-center justify-content-center gap-1.5 mt-2 shadow-sm"
                            style={{ borderRadius: '8px' }}
                            disabled={cart.length === 0}
                            id="btn-finalize-sale"
                          >
                            <i className="bi bi-cash-coin fs-5"></i>
                            <span>COBRAR Y EMITIR RECIBO</span>
                          </button>

                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              )}

              {/* VISTA 6: GESTIÓN DE USUARIOS */}
              {screen === 'users' && (
                <div className="fade-in">
                  <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                      <h4 className="fw-bold mb-0 text-dark">
                        <i className="bi bi-people-fill text-warning me-2"></i>
                        Gestión de Usuarios Activos
                      </h4>
                      <p className="text-secondary small mb-0 mt-1">
                        Control de credenciales y roles para administradores y cajeros.
                      </p>
                    </div>
                    <button className="btn btn-warning fw-bold text-dark px-4 shadow-sm" style={{ borderRadius: '6px' }}>
                      <i className="bi bi-person-plus-fill me-1"></i>
                      Crear Nuevo Usuario
                    </button>
                  </div>

                  <div className="card border-0 shadow-sm" style={{ borderRadius: '12px', overflow: 'hidden' }}>
                    <div className="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                      <h6 className="mb-0 fw-bold text-secondary">Identidades del Sistema</h6>
                      <div className="input-group" style={{ width: '250px' }}>
                        <span className="input-group-text bg-white border-end-0 text-muted"><i className="bi bi-search"></i></span>
                        <input type="text" className="form-control border-start-0 ps-0" placeholder="Buscar empleado..." style={{ fontSize: '0.85rem' }} aria-label="Buscar empleado" />
                      </div>
                    </div>
                    <div className="table-responsive">
                      <table className="table table-hover align-middle mb-0 text-nowrap">
                        <thead className="bg-light text-muted" style={{ fontSize: '0.8rem' }}>
                          <tr>
                            <th className="ps-4 fw-semibold text-uppercase">Nombre y Apellido</th>
                            <th className="fw-semibold text-uppercase">Email de Acceso</th>
                            <th className="fw-semibold text-uppercase">Rol</th>
                            <th className="fw-semibold text-uppercase">Estado</th>
                            <th className="fw-semibold text-uppercase">Creación</th>
                            <th className="text-end pe-4 fw-semibold text-uppercase">Acciones</th>
                          </tr>
                        </thead>
                        <tbody style={{ fontSize: '0.9rem' }}>
                          {users.map(u => (
                            <tr key={u.id}>
                              <td className="ps-4 fw-medium text-dark">{u.name}</td>
                              <td className="text-muted">{u.email}</td>
                              <td>{u.role === 'admin' ? <span className="badge bg-danger text-white">Administrador</span> : <span className="badge bg-primary text-white">Cajero POS</span>}</td>
                              <td>{u.status === 'active' ? <span className="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Activo</span> : <span className="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Inactivo</span>}</td>
                              <td className="text-muted font-mono">{u.createdAt}</td>
                              <td className="text-end pe-4">
                                <button className="btn btn-sm btn-outline-primary py-1 px-2 border-0" title="Editar usuario" aria-label="Editar usuario"><i className="bi bi-pencil"></i></button>
                                <button className="btn btn-sm btn-outline-danger py-1 px-2 border-0" title="Eliminar usuario" aria-label="Eliminar usuario"><i className="bi bi-trash"></i></button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

              {/* VISTA 7: REPORTES Y VENTAS */}
              {screen === 'reports' && (
                <div className="fade-in">
                  <div className="d-flex justify-content-between align-items-center mb-4">
                    <div>
                      <h4 className="fw-bold mb-0 text-dark">
                        <i className="bi bi-graph-up-arrows text-warning me-2"></i>
                        Reportes Contables y Ventas
                      </h4>
                      <p className="text-secondary small mb-0 mt-1">
                        Historial consolidado de comprobantes emitidos.
                      </p>
                    </div>
                    <div className="d-flex gap-2">
                       <button className="btn btn-outline-secondary fw-bold bg-white shadow-sm" style={{ borderRadius: '6px' }}>
                        <i className="bi bi-calendar3 me-2"></i>
                        Hoy
                      </button>
                      <button className="btn btn-dark fw-bold text-white shadow-sm" style={{ borderRadius: '6px' }}>
                        <i className="bi bi-file-earmark-excel-fill me-2 text-success"></i>
                        Exportar a Excel
                      </button>
                    </div>
                  </div>

                  <div className="row g-3 mb-4">
                    <div className="col-12 col-md-4">
                      <div className="card border-0 shadow-sm h-100 p-4" style={{ borderRadius: '12px' }}>
                        <div className="d-flex align-items-center mb-2 text-muted fw-semibold">
                          <i className="bi bi-cash-stack me-2 fs-5 text-success"></i>
                          Ingresos del Día
                        </div>
                        <h3 className="fw-bold text-dark mb-0">${transactions.reduce((acc, sum) => acc + sum.total, 0).toFixed(2)}</h3>
                        <p className="small text-success mb-0 mt-1"><i className="bi bi-arrow-up-right me-1"></i> +12% sobre ayer</p>
                      </div>
                    </div>
                    <div className="col-12 col-md-4">
                      <div className="card border-0 shadow-sm h-100 p-4" style={{ borderRadius: '12px' }}>
                        <div className="d-flex align-items-center mb-2 text-muted fw-semibold">
                          <i className="bi bi-receipt me-2 fs-5 text-primary"></i>
                          Boletas Emitidas
                        </div>
                        <h3 className="fw-bold text-dark mb-0">{transactions.length}</h3>
                        <p className="small text-muted mb-0 mt-1">Transacciones procesadas</p>
                      </div>
                    </div>
                    <div className="col-12 col-md-4">
                      <div className="card border-0 shadow-sm h-100 p-4" style={{ borderRadius: '12px' }}>
                        <div className="d-flex align-items-center mb-2 text-muted fw-semibold">
                          <i className="bi bi-box-seam me-2 fs-5 text-warning"></i>
                          Ticket Promedio
                        </div>
                        <h3 className="fw-bold text-dark mb-0">${(transactions.reduce((acc, sum) => acc + sum.total, 0) / (transactions.length || 1)).toFixed(2)}</h3>
                        <p className="small text-muted mb-0 mt-1">Por cada cobro en POS</p>
                      </div>
                    </div>
                  </div>

                  <div className="card border-0 shadow-sm" style={{ borderRadius: '12px', overflow: 'hidden' }}>
                    <div className="card-header bg-white border-bottom py-3">
                      <h6 className="mb-0 fw-bold text-secondary">Últimas Transacciones Registradas</h6>
                    </div>
                    <div className="table-responsive">
                      <table className="table table-hover align-middle mb-0 text-nowrap">
                        <thead className="bg-light text-muted" style={{ fontSize: '0.8rem' }}>
                          <tr>
                            <th className="ps-4 fw-semibold text-uppercase">Nº Recibo</th>
                            <th className="fw-semibold text-uppercase">Fecha y Hora</th>
                            <th className="fw-semibold text-uppercase">Atendido Por</th>
                            <th className="fw-semibold text-uppercase">Ítems</th>
                            <th className="fw-semibold text-uppercase">Subtotal</th>
                            <th className="fw-semibold text-uppercase text-end pe-4">Total</th>
                          </tr>
                        </thead>
                        <tbody style={{ fontSize: '0.9rem' }}>
                          {transactions.map(tx => (
                            <tr key={tx.id}>
                              <td className="ps-4 fw-bold text-dark font-mono">{tx.invoiceNumber}</td>
                              <td className="text-secondary font-mono">{tx.date}</td>
                              <td><span className="badge bg-light text-dark border"><i className="bi bi-person-fill text-muted me-1"></i> {tx.cashier}</span></td>
                              <td className="text-center font-mono bg-light text-secondary rounded">{tx.items.length}</td>
                              <td className="font-mono text-muted">${tx.subtotal.toFixed(2)}</td>
                              <td className="text-end pe-4 font-mono fw-bold text-success">${tx.total.toFixed(2)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

            </main>

            {/* Pie de Página Administrativo */}
            <footer className="bg-white border-top py-2.5 text-center text-muted mt-auto" style={{ fontSize: '0.8rem' }}>
              <div>
                <strong>Ferretería El Tornillo S.A.</strong> Matrix Administrativa | Panel de Control.
              </div>
            </footer>

          </div>

        </div>
      )}

      {/* ---------------- REGISTRO TÉRMICO / INVOICE MODAL ---------------- */}
      {showReceipt && invoice && (
        <div className="position-fixed top-0 inset-s-0 w-100 h-100 bg-dark bg-opacity-75 d-flex align-items-center justify-content-center z-3 p-3">
          <div className="card border-0 shadow-lg p-0 bg-white" style={{ maxWidth: '380px', width: '100%', borderRadius: '12px' }}>
            <div className="card-header bg-dark text-white py-2.5 d-flex align-items-center justify-content-between">
              <span className="fw-bold fs-7 text-uppercase mb-0 tracking-wide font-mono"><i className="bi bi-printer-fill me-1 text-warning"></i> Impresión de Boleta</span>
              <button onClick={() => setShowReceipt(false)} className="btn-close btn-close-white" aria-label="Close"></button>
            </div>
            
            {/* Recibo térmico */}
            <div className="p-4" style={{ backgroundColor: '#fffff8', overflow: 'hidden' }} id="printableReceipt">
              <div className="text-center font-mono border-bottom pb-3 mb-3 text-dark">
                <i className="bi bi-nut-fill fs-2 text-warning d-block mb-1"></i>
                <h6 className="fw-bold mb-0 text-uppercase" style={{ letterSpacing: '1px' }}>FERRETERÍA EL TORNILLO S.A.</h6>
                <small className="d-block" style={{ fontSize: '0.72rem' }}>AV. CONST. INDUSTRIAL N.812</small>
                <small className="d-block" style={{ fontSize: '0.72rem' }}>RUC: 1792348512001 | TEL: 09-1234567</small>
                <small className="d-block" style={{ fontSize: '0.72rem' }}>--- MATRIZ CENTRAL ---</small>
              </div>

              <div className="font-mono text-dark" style={{ fontSize: '0.78rem' }}>
                <p className="mb-1"><strong>Nº Boleta:</strong> {invoice.invoiceNumber}</p>
                <p className="mb-1"><strong>Fecha:</strong> {invoice.date}</p>
                <p className="mb-1"><strong>Cajero:</strong> {invoice.cashier}</p>
                <p className="mb-1"><strong>Pago:</strong> <span className="text-uppercase">{invoice.paymentMethod}</span></p>
                <div className="border-bottom my-2"></div>
                <p className="mb-1"><strong>Cliente:</strong> {invoice.customerName}</p>
                <p className="mb-2"><strong>RUC/DNI:</strong> {invoice.customerDni}</p>

                <div className="border-bottom border-secondary border-dashed my-2"></div>
                <table className="w-100 text-xs table table-sm table-borderless mb-2">
                  <thead>
                    <tr className="fw-bold text-dark">
                      <th className="p-0">Material</th>
                      <th className="text-center p-0">Cant</th>
                      <th className="text-end p-0">Monto</th>
                    </tr>
                  </thead>
                  <tbody>
                    {invoice.items.map((item, idx) => (
                      <tr key={idx}>
                        <td className="p-0 text-truncate" style={{ maxWidth: '180px' }}>{item.product.name}</td>
                        <td className="text-center p-0 font-mono">{item.quantity}</td>
                        <td className="text-end p-0 font-mono">${(item.product.price * item.quantity).toFixed(2)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>

                <div className="border-top border-secondary border-dashed pt-2 my-2"></div>
                
                <div className="d-flex justify-content-between mb-0.5">
                  <span>Subtotal Neto:</span>
                  <span className="fw-bold font-mono">${invoice.subtotal.toFixed(2)}</span>
                </div>
                <div className="d-flex justify-content-between mb-0.5">
                  <span>IVA Base (12%):</span>
                  <span className="fw-bold font-mono">${invoice.tax.toFixed(2)}</span>
                </div>
                <div className="d-flex justify-content-between fs-6 fw-extrabold border-top border-dark pt-1 mt-1 text-primary">
                  <span>TOTAL COBRADO:</span>
                  <span className="font-mono">${invoice.total.toFixed(2)}</span>
                </div>
              </div>

              <div className="text-center font-mono text-dark mt-4 pt-3 border-top border-dashed border-dark">
                <p className="mb-1 small">** GRACIAS POR SU COMPRA **</p>
                <small className="d-block text-muted" style={{ fontSize: '0.65rem' }}>Verifique su mercadería antes de salir de la ferretería.</small>
              </div>
            </div>

            <div className="card-footer bg-light p-3 d-flex gap-2">
              <button 
                onClick={() => { window.print(); }}
                className="btn btn-dark btn-sm grow fw-bold d-flex align-items-center justify-content-center gap-1"
              >
                <i className="bi bi-printer"></i>
                <span>Imprimir Ticket</span>
              </button>
              <button 
                onClick={() => setShowReceipt(false)}
                className="btn btn-outline-secondary btn-sm"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
