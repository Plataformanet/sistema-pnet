<script setup lang="ts">
import { DollarSign } from "lucide-vue-next";
import FinancialSimpleCard from "./FinancialSimpleCard.vue";
</script>

<template>
    <FinancialSimpleCard
        title="Receita do Mês"
        value="R$ 45.231,89"
        percentage="+12.5%"
        percentageType="up"
        :isGreen="true"
        :icon="DollarSign"
        comparisonText="vs. mês passado"
    />
</template>
