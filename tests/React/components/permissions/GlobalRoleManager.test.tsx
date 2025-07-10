import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi } from 'vitest';
import { GlobalRoleManager } from '@/components/permissions/GlobalRoleManager';

// Mock the hooks
vi.mock('@/hooks/usePermissions', () => ({
  usePermissions: () => ({
    options: {
      roles: [
        { value: 'admin', label: 'Administrador', description: 'Control total' },
        { value: 'member', label: 'Miembro', description: 'Acceso básico' },
      ],
      permissions: {
        'Gestión del Espacio': [
          { value: 'manage_space', label: 'Administrar espacio', description: 'Puede administrar el espacio' },
          { value: 'view_space', label: 'Ver espacio', description: 'Puede ver el espacio' },
        ],
      },
      permission_details: [
        { value: 'manage_space', label: 'Administrar espacio', description: 'Puede administrar el espacio' },
        { value: 'view_space', label: 'Ver espacio', description: 'Puede ver el espacio' },
      ],
    },
    loading: false,
    error: null,
    isPermissionGrantedByRole: (role: string, permission: string) => {
      if (role === 'admin') return true;
      if (role === 'member' && permission === 'view_space') return true;
      return false;
    },
  }),
}));

vi.mock('@/composables/useFeature', () => ({
  useFeature: () => true,
}));

describe('GlobalRoleManager', () => {
  const defaultProps = {
    userId: 1,
    userName: 'John Doe',
    currentRole: 'member',
    spaceId: 'test-space',
    onRoleChange: vi.fn(),
    readOnly: false,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders role selector and permissions table', () => {
    render(<GlobalRoleManager {...defaultProps} />);
    
    expect(screen.getByText('Rol Global en el Espacio')).toBeInTheDocument();
    expect(screen.getByText('Miembro')).toBeInTheDocument();
    expect(screen.getByText('Gestión del Espacio')).toBeInTheDocument();
  });

  it('shows correct permissions for member role', () => {
    render(<GlobalRoleManager {...defaultProps} />);
    
    const permissions = screen.getAllByText(/Permitido|Denegado/);
    expect(permissions).toHaveLength(2);
  });

  it('shows save button when role changes', async () => {
    render(<GlobalRoleManager {...defaultProps} />);
    
    // Change role
    const roleSelector = screen.getByRole('combobox');
    fireEvent.click(roleSelector);
    
    const adminOption = await screen.findByText('Administrador');
    fireEvent.click(adminOption);
    
    expect(screen.getByText('Guardar Cambios')).toBeInTheDocument();
  });

  it('calls onRoleChange when saving', async () => {
    render(<GlobalRoleManager {...defaultProps} />);
    
    // Change role
    const roleSelector = screen.getByRole('combobox');
    fireEvent.click(roleSelector);
    
    const adminOption = await screen.findByText('Administrador');
    fireEvent.click(adminOption);
    
    // Save changes
    const saveButton = screen.getByText('Guardar Cambios');
    fireEvent.click(saveButton);
    
    await waitFor(() => {
      expect(defaultProps.onRoleChange).toHaveBeenCalledWith('admin');
    });
  });

  it('respects readOnly prop', () => {
    render(<GlobalRoleManager {...defaultProps} readOnly={true} />);
    
    const roleSelector = screen.getByRole('combobox');
    expect(roleSelector).toBeDisabled();
  });

  it('does not render when feature is disabled', () => {
    vi.mocked(useFeature).mockReturnValueOnce(false);
    
    const { container } = render(<GlobalRoleManager {...defaultProps} />);
    expect(container.firstChild).toBeNull();
  });
});