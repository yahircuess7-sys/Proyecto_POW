// Definición de tipos y estructuras de datos para el sistema de Ferretería El Tornillo

export type Role = 'admin' | 'cajero';

export type Screen = 'home' | 'login' | 'dashboard' | 'inventory' | 'inventory_form' | 'sales_pos' | 'users' | 'reports';

export type TabMode = 'interactive' | 'code_viewer';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
  status: 'active' | 'inactive';
  createdAt: string;
}

export interface Product {
  id: number;
  sku: string;
  name: string;
  description: string;
  price: number;
  stock: number;
  category: string;
  unit: string;
  brand?: string;
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface SaleTransaction {
  id: string;
  invoiceNumber: string;
  date: string;
  items: CartItem[];
  subtotal: number;
  tax: number; // IVA 12% o 15% según convención de latam
  total: number;
  cashier: string;
  customerName: string;
  customerDni: string;
  paymentMethod: 'efectivo' | 'tarjeta' | 'transferencia';
}

export interface BladeFile {
  path: string;
  title: string;
  description: string;
  icon: string;
  code: string;
}
