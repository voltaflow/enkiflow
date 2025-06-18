import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Copy, CalendarIcon, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface DuplicateDayActionProps {
    onDuplicate: (fromDate: Date, toDate: Date) => Promise<void>;
    disabled?: boolean;
    className?: string;
}

export function DuplicateDayAction({
    onDuplicate,
    disabled = false,
    className
}: DuplicateDayActionProps) {
    const [showDialog, setShowDialog] = useState(false);
    const [fromDate, setFromDate] = useState<Date | undefined>(new Date());
    const [toDate, setToDate] = useState<Date | undefined>(new Date());
    const [isLoading, setIsLoading] = useState(false);

    const handleDuplicate = async () => {
        if (!fromDate || !toDate) return;

        setIsLoading(true);
        try {
            await onDuplicate(fromDate, toDate);
            setShowDialog(false);
            // Reset dates
            setFromDate(new Date());
            setToDate(new Date());
        } catch (error) {
            console.error('Error duplicating day:', error);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <>
            <Button
                variant="outline"
                onClick={() => setShowDialog(true)}
                disabled={disabled}
                className={cn('w-full', className)}
            >
                <Copy className="h-4 w-4 mr-2" />
                Duplicar día anterior
            </Button>

            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Duplicar entradas de tiempo</DialogTitle>
                        <DialogDescription>
                            Copia todas las entradas de tiempo de un día a otro. Las duraciones se reiniciarán a cero.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Copiar desde:</label>
                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className={cn(
                                            'w-full justify-start text-left font-normal',
                                            !fromDate && 'text-muted-foreground'
                                        )}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {fromDate ? format(fromDate, 'PPP', { locale: es }) : 'Seleccionar fecha'}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0" align="start">
                                    <Calendar
                                        mode="single"
                                        selected={fromDate}
                                        onSelect={setFromDate}
                                        initialFocus
                                        locale={es}
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Copiar hacia:</label>
                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className={cn(
                                            'w-full justify-start text-left font-normal',
                                            !toDate && 'text-muted-foreground'
                                        )}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {toDate ? format(toDate, 'PPP', { locale: es }) : 'Seleccionar fecha'}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0" align="start">
                                    <Calendar
                                        mode="single"
                                        selected={toDate}
                                        onSelect={setToDate}
                                        initialFocus
                                        locale={es}
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowDialog(false)}
                            disabled={isLoading}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleDuplicate}
                            disabled={!fromDate || !toDate || isLoading}
                        >
                            {isLoading ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Duplicando...
                                </>
                            ) : (
                                <>
                                    <Copy className="h-4 w-4 mr-2" />
                                    Duplicar
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}