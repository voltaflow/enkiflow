import React, { useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle, Clock } from 'lucide-react';

interface IdlePromptModalProps {
    idleMinutes: number;
    onKeepTime: () => void;
    onDiscardTime: (minutes: number) => void;
    isOpen?: boolean;
}

export function IdlePromptModal({
    idleMinutes,
    onKeepTime,
    onDiscardTime,
    isOpen = true
}: IdlePromptModalProps) {
    const [customMinutes, setCustomMinutes] = useState(idleMinutes);
    const [useCustom, setUseCustom] = useState(false);

    const handleDiscardTime = () => {
        const minutesToDiscard = useCustom ? customMinutes : idleMinutes;
        onDiscardTime(minutesToDiscard);
    };

    const formatDuration = (minutes: number) => {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        
        if (hours > 0) {
            return `${hours} hora${hours > 1 ? 's' : ''} y ${mins} minuto${mins !== 1 ? 's' : ''}`;
        }
        return `${mins} minuto${mins !== 1 ? 's' : ''}`;
    };

    return (
        <Dialog open={isOpen} modal>
            <DialogContent 
                className="sm:max-w-[500px]" 
                onPointerDownOutside={(e) => e.preventDefault()}
                onEscapeKeyDown={(e) => e.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <AlertCircle className="h-5 w-5 text-yellow-500" />
                        Inactividad Detectada
                    </DialogTitle>
                    <DialogDescription>
                        Hemos detectado que has estado inactivo durante{' '}
                        <strong>{formatDuration(idleMinutes)}</strong>.
                    </DialogDescription>
                </DialogHeader>

                <div className="py-4 space-y-4">
                    <div className="bg-muted p-4 rounded-lg">
                        <div className="flex items-center gap-3">
                            <Clock className="h-8 w-8 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium">¿Qué deseas hacer con este tiempo?</p>
                                <p className="text-sm text-muted-foreground mt-1">
                                    Puedes mantener todo el tiempo registrado o descartar el período de inactividad.
                                </p>
                            </div>
                        </div>
                    </div>

                    {useCustom && (
                        <div className="space-y-2">
                            <Label htmlFor="custom-minutes">Minutos a descartar:</Label>
                            <Input
                                id="custom-minutes"
                                type="number"
                                min="0"
                                max={idleMinutes}
                                value={customMinutes}
                                onChange={(e) => setCustomMinutes(parseInt(e.target.value) || 0)}
                                className="w-full"
                            />
                            <p className="text-xs text-muted-foreground">
                                Puedes ajustar la cantidad de tiempo inactivo a descartar (máximo {idleMinutes} minutos).
                            </p>
                        </div>
                    )}

                    {!useCustom && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setUseCustom(true)}
                            className="text-sm"
                        >
                            Ajustar tiempo manualmente
                        </Button>
                    )}
                </div>

                <DialogFooter className="flex gap-2 sm:gap-0">
                    <Button
                        variant="secondary"
                        onClick={onKeepTime}
                        className="flex-1 sm:flex-none"
                    >
                        Mantener tiempo
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={handleDiscardTime}
                        className="flex-1 sm:flex-none"
                    >
                        Descartar {useCustom ? `${customMinutes} min` : 'tiempo inactivo'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}