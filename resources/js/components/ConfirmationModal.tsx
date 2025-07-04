import { Button } from '@/components/ui/button';
import React from 'react';

interface ConfirmationModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    isDestructive?: boolean;
}

export function ConfirmationModal({
    isOpen,
    onClose,
    onConfirm,
    title,
    message,
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    isDestructive = false,
}: ConfirmationModalProps) {
    if (!isOpen) return null;

    const handleConfirm = () => {
        onConfirm();
        onClose();
    };

    const handleBackdropClick = (e: React.MouseEvent) => {
        if (e.target === e.currentTarget) {
            onClose();
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center" onClick={handleBackdropClick}>
            {/* Backdrop */}
            <div className="fixed inset-0 bg-black/80 backdrop-blur-sm" />

            {/* Modal */}
            <div className="bg-background border-border relative mx-4 w-full max-w-md space-y-4 rounded-lg border p-6 shadow-lg">
                {/* Header */}
                <div>
                    <h2 className="text-foreground text-lg font-semibold">{title}</h2>
                    <p className="text-muted-foreground mt-2 text-sm">{message}</p>
                </div>

                {/* Footer */}
                <div className="flex justify-end gap-3 pt-2">
                    <Button variant="outline" onClick={onClose}>
                        {cancelText}
                    </Button>
                    <Button variant={isDestructive ? 'destructive' : 'default'} onClick={handleConfirm}>
                        {confirmText}
                    </Button>
                </div>
            </div>
        </div>
    );
}
