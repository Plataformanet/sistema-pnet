<script setup lang="ts">
import { DollarSign } from "lucide-vue-next";
import FinancialSimpleCard from "./FinancialSimpleCard.vue";
</script>

<template>
    <FinancialSimpleCard
        title="Saldo Atual"
        value="R$ 32.500,00"
        percentage="+7.5%"
        percentageType="up"
        :isGreen="true"
        :icon="DollarSign"
        comparisonText="vs. mês passado"
    />
</template>
