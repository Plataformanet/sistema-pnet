import { watch, ref } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";

interface ContactForm {
    type?: string;
    cpf_cnpj: string;
    name_corporatereason?: string;
    fantasy_name?: string;
    email?: string;
    phone?: string;
    cell_phone?: string;
    zip_code?: string;
    street?: string;
    number?: string;
    complement?: string;
    neighborhood?: string;
    city?: string;
    state?: string;
    [key: string]: any;
}

export function useContactLookup(
    form: ContactForm,
    entityType: "clients" | "suppliers" | "employees",
    isEdit: boolean = false
) {
    const isLoadingContact = ref(false);
    const lastSearched = ref("");

    // If it's edit mode, we do not want to run the automatic lookup since the contact already exists
    if (isEdit) {
        return { isLoadingContact };
    }

    watch(
        () => form.cpf_cnpj,
        async (newCpfCnpj) => {
            if (!newCpfCnpj) return;

            // Remove formatting to get raw digits
            const cleanVal = newCpfCnpj.replace(/\D/g, "");

            // We only search if it's a complete CPF (11 digits) or CNPJ (14 digits)
            if (
                (cleanVal.length === 11 || cleanVal.length === 14) &&
                cleanVal !== lastSearched.value
            ) {
                lastSearched.value = cleanVal;
                isLoadingContact.value = true;

                try {
                    const response = await axios.get(
                        `/registrations/${entityType}/get-contact-by-cpf-cnpj/${cleanVal}`
                    );
                    const contact = response.data;

                    if (contact && contact.id) {
                        // Prefill basic fields if they exist in the form
                        if ("name_corporatereason" in form) {
                            form.name_corporatereason = contact.name_corporatereason || "";
                        }
                        if ("fantasy_name" in form) {
                            form.fantasy_name = contact.fantasy_name || "";
                        }
                        if ("email" in form) {
                            form.email = contact.email || "";
                        }
                        if ("phone" in form) {
                            form.phone = contact.phone || "";
                        }
                        if ("cell_phone" in form) {
                            form.cell_phone = contact.cell_phone || "";
                        }

                        // Prefill address fields if relation exists and fields exist in form
                        if (contact.address) {
                            if ("zip_code" in form) {
                                form.zip_code = contact.address.zip_code || "";
                            }
                            if ("street" in form) {
                                form.street = contact.address.street || "";
                            }
                            if ("number" in form) {
                                form.number = contact.address.number || "";
                            }
                            if ("complement" in form) {
                                form.complement = contact.address.complement || "";
                            }
                            if ("neighborhood" in form) {
                                form.neighborhood = contact.address.neighborhood || "";
                            }
                            if ("city" in form) {
                                form.city = contact.address.city || "";
                            }
                            if ("state" in form) {
                                form.state = contact.address.state || "";
                            }
                        }

                        toast.success("Dados do contato preenchidos automaticamente!");
                    }
                } catch (error) {
                    console.error("Erro ao pesquisar contato por CPF/CNPJ:", error);
                } finally {
                    isLoadingContact.value = false;
                }
            }
        }
    );

    return {
        isLoadingContact,
    };
}
