function calculatePrice(quantities, demand_intercept, demand_slope){
    // linear demand function: price vs quantity is linear function, so we calculuate using slope-intercept
    // quantities param should be an array to generalize to n-opoly, in which n players split the demand
    // demand intercept/slope are just slope and y intercept of price vs quantity plot, called the demand curve
    return demand_intercept - demand_slope * sumArr(quantities);
}

function calculatePriceSimple(quantity, demand_intercept, demand_slope){
    return demand_intercept - demand_slope * quantity;
}

function calculateTotalCost(quantity, fixed_cost, unit_cost){
    // fixed cost: cost before producing any output
    // unit cost: additional cost for each unit of output
    return fixed_cost + quantity * unit_cost;
}

function calculatePriceHistory(quantity_histories, demand_intercept, demand_slope){
    return zipMin2DArr(quantity_histories).map( quantitiesForAYear => calculatePrice(quantitiesForAYear, demand_intercept, demand_slope) );
}

function calculatePriceHistorySimple(quantity_history, demand_intercept, demand_slope){
    return quantity_history.map( q => calculatePriceSimple(q, demand_intercept, demand_slope) );
}

function calculateTotalCostHistory(quantity_history, fixed_cost, unit_cost){
    return quantity_history.map( q => calculateTotalCost(q, fixed_cost, unit_cost) );
}

function totalCostHistoryFunctionForGame(fixedCost, unitCost){
    return quantityHistory => calculateTotalCostHistory(quantityHistory, fixedCost, unitCost);
}

/*
function calculateAverageCostHistory(quantities_history, fixed_cost, unit_cost){
    return calculateTotalCostHistory(quantities_history, fixed_cost, unit_cost).map( (c, i) => c / float(quantities_histories[i]) );
}
*/

function calculateAvgCostHistory(totalCostHistory, quantityHistory){
    return totalCostHistory.map( (c, i) => c / quantityHistory[i] );
}

function calculateRevenueHistory(priceHistory, quantityHistory){
    return priceHistory.map( (p, i) => p * quantityHistory[i] );
}

function calculateProfitHistory(revenueHistory, totalCostHistory){
    return revenueHistory.map( (r, i) => r - totalCostHistory[i]);
}

function calculateReturnHistory(profitHistory, totalCostHistory){
    return profitHistory.map( (pf, i) => pf / totalCostHistory );
}

function calculateStatisticHistory(statistic, priceHistory, totalCostHistoryFunction, quantityHistory){
        // compute histories in order of a topological sort of history dependencies DAG

        const matchAgainst = statistic.toLowerCase();

        if( matchAgainst == "quantity")		return quantityHistory;

        if( matchAgainst == "price")		return priceHistory;

        revenueHistory = calculateRevenueHistory(priceHistory, quantityHistory);
        if( matchAgainst == "revenue" )		return revenueHistory;

        totalCostHistory = totalCostHistoryFunction(quantityHistory);
        if( matchAgainst == "totalCost" )	return totalCostHistory;

        profitHistory = calculateProfitHistory(revenueHistory, totalCostHistory);
        if( matchAgainst == "profit" )		return profitHistory;

        returnHistory = calculateReturnHistory(profitHistory, totalCostHistory);
        if( matchAgainst == "return" )		return returnHistory;

        avgCostHistory = calculateAvgCostHistory(totalCostHistory, quantityHistory);
        if( matchAgainst == "avgCost" )	    return avgCostHistory;
}

function calculateNOpolyEquilibriumQuantity(demand_intercept, demand_slope, unit_cost, number_firms){
    /*  The equilibrium quantity each firm produces in a market with n firms (an n-opoly)
        producing identical products with identical, constant marginal costs */
    // math:
    //  P = P_1 = ... = P_n = I - S*sum(k, Q_k)
    //  TC = TC_1 = ... = TC_n = F + U*Q
    //  R_j = P * Q_j = I*Q_j - S*sum(k, Q_k)*Q_j = I*Q_j - S*sum(k!=j, Q_k)*Q_j - S*Q_j^2
    //  MR_j = dR_j/dQ_j = I - S*sum(k != j, Q_k) - 2*S*Q_j
    //  MC_1 = ... = MC_n = U
    //  at firm j's optimal output, MR_j = MC_j
    //      I - S*(sum(k != j, Q_k)) - 2*S*Q_j = U
    //      Q_j = [ I - U - S*sum(k != j, Q_k) ]/(2S) = 1/2 * [ (I - U)/S - sum(k != j, Q_k) ]
    // at equilibrium, Q_1 = ... = Q_n
    //      Q_j = 1/2 * [ (I - U)/S - sum(k != j, Q_k) ] = 1/2 * [ (I - U)/S - (n-1)*Q_j ]
    //      2*Q_j = (I - U)/S - (n-1)*Q_j
    //      (n+1)*Q_j = (I - U)/S
    //      Q_j = (I - U) / ((n+1) * S)
    return (demand_intercept - unit_cost)/(demand_slope * (number_firms + 1));
}

function calculateNOpolyEquilibriumPrice(demand_intercept, demand_slope, unit_cost, number_firms){
    // intercept - slope * (sum of eq quants) = intercept - slope * (number_firms * eq quant)
    return demand_intercept - demand_slope * number_firms * calculateNOpolyEquilibriumQuantity(demand_intercept, demand_slope, unit_cost, number_firms);
}

function calculateNOpolyEquilibriumRevenue(demand_intercept, demand_slope, unit_cost, number_firms){
    // eq price * eq quant
    return calculateNOpolyEquilibriumQuantity(demand_intercept, demand_slope, unit_cost, number_firms) * calculateNOpolyEquilibriumPrice(demand_intercept, demand_slope, unit_cost, number_firms);
}

function calculateNOpolyEquilibriumProfit(demand_intercept, demand_slope, unit_cost, fixed_cost, number_firms){
    return calculateNOpolyEquilibriumRevenue(demand_intercept, demand_slope, unit_cost, number_firms) - (fixed_cost + unit_cost * calculateNOpolyEquilibriumQuantity(demand_intercept, demand_slope, unit_cost, number_firms));
}

function calculateNOpolyEquilibriumOf(statistic, demand_intercept, demand_slope, unit_cost, fixed_cost, number_firms){
    switch(statistic.toLowerCase()){
        case "quantity":
            return calculateNOpolyEquilibriumQuantity(demand_intercept, demand_slope, unit_cost, number_firms);
            break;
        case "price":
            return calculateNOpolyEquilibriumPrice(demand_intercept, demand_slope, unit_cost, number_firms);
            break;
        case "revenue":
            return calculateNOpolyEquilibriumRevenue(demand_intercept, demand_slope, unit_cost, number_firms);
            break;
        case "profit":
            return calculateNOpolyEquilibriumProfit(demand_intercept, demand_slope, unit_cost, fixed_cost, number_firms);
            break;
        default:
            return null;
            break;
    }
}

function calculateNOpolyMaximumQuantity(demand_intercept, demand_slope, number_firms){
    return ((demand_intercept / demand_slope) / number_firms) - 1;
}

// ensure positive price
/*
function calculateMaxQuantity(market_structure, demand_intercept, demand_slope){
    switch(market_structure.toLowerCase()){
        case "monopoly":
            return calculateNOpolyMaximumQuantity(demand_interept, demand_slope, 1);
            break;
        case "oligopoly":
            return calculateNOpolyMaximumQuantity(demand_interept, demand_slope, 2);
            break;
        default:
            return 500;
    }
}
*/
// ensure positive price
function calculateMaxQuantity(gameInfo){
    switch(gameInfo['marketStructureName'].toLowerCase()){
        case "monopoly":
            return calculateNOpolyMaximumQuantity(gameInfo['demandIntercept'], gameInfo['demandSlope'], 1);
            break;
        case "oligopoly":
            return calculateNOpolyMaximumQuantity(gameInfo['demandIntercept'], gameInfo['demandSlope'], 2);
            break;
        default:
            return 500;
    }
}
