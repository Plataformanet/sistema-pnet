import { watch, ref } from "vue";
import { toast } from "vue-sonner";

interface AddressForm {
    zip_code: string;
    street: string;
    neighborhood: string;
    city: string;
    state: string;
    [key: string]: any;
}

export function useCepLookup(form: AddressForm) {
    const isLoadingCep = ref(false);

    watch(
        () => form.zip_code,
        async (newCep) => {
            if (!newCep) return;
            
            // Remove any non-digits (formatting)
            const cleanCep = newCep.replace(/\D/g, "");

            // ViaCEP API requires exactly 8 digits
            if (cleanCep.length === 8) {
                isLoadingCep.value = true;
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
                    if (!response.ok) {
                        throw new Error("Erro na requisição");
                    }
                    const data = await response.json();
                    
                    if (data.erro) {
                        toast.error("CEP não encontrado.");
                        return;
                    }

                    // Populate form fields
                    form.street = data.logradouro || "";
                    form.neighborhood = data.bairro || "";
                    form.city = data.localidade || "";
                    form.state = data.uf || "";
                    
                    toast.success("Endereço preenchido com sucesso!");
                } catch (error) {
                    console.error("Erro ao buscar CEP:", error);
                    toast.error("Falha ao buscar informações do CEP.");
                } finally {
                    isLoadingCep.value = false;
                }
            }
        }
    );

    return {
        isLoadingCep,
    };
}
